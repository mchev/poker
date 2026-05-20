<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\SchedulingRoundStatus;
use App\Mail\NewPollOpenedMail;
use App\Mail\NewProposedDateMail;
use App\Mail\ParticipantWelcomeMail;
use App\Mail\TournamentConfirmedMail;
use App\Models\Participant;
use App\Models\ProposedDate;
use App\Models\SchedulingRound;
use App\Models\Vote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PokerSchedulingService
{
    public function __construct(private BrevoContactService $brevoContacts) {}

    public function activeRound(): SchedulingRound
    {
        $round = SchedulingRound::query()
            ->whereIn('status', [
                SchedulingRoundStatus::Polling,
                SchedulingRoundStatus::Confirmed,
            ])
            ->latest('id')
            ->first();

        if ($round instanceof SchedulingRound) {
            return $round;
        }

        return SchedulingRound::query()->create([
            'status' => SchedulingRoundStatus::Polling,
        ]);
    }

    public function subscribe(string $name, string $email): Participant
    {
        $participant = Participant::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name],
        );

        Mail::to($participant->email)->send(new ParticipantWelcomeMail($participant));

        $this->brevoContacts->syncParticipant($participant);

        return $participant;
    }

    public function sendAccessLink(Participant $participant): void
    {
        Mail::to($participant->email)->send(new ParticipantWelcomeMail($participant));
    }

    public function findParticipantByToken(?string $token): ?Participant
    {
        if (blank($token)) {
            return null;
        }

        return Participant::query()->where('token', $token)->first();
    }

    public function proposeDate(Participant $participant, Carbon $startsAt, string $location, ?string $theme = null): ProposedDate
    {
        $round = $this->activeRound();

        abort_unless($round->isPolling(), 403, 'Les propositions de dates ne sont pas ouvertes pour le moment.');

        $proposedDate = ProposedDate::query()->firstOrCreate(
            [
                'scheduling_round_id' => $round->id,
                'starts_at' => $startsAt,
            ],
            [
                'proposed_by_participant_id' => $participant->id,
                'location' => $location,
                'theme' => filled($theme) ? trim($theme) : null,
            ],
        );

        if ($proposedDate->wasRecentlyCreated) {
            Participant::query()
                ->whereKeyNot($participant->id)
                ->each(function (Participant $recipient) use ($proposedDate, $participant): void {
                    Mail::to($recipient->email)->send(
                        new NewProposedDateMail(
                            participant: $recipient,
                            proposedDate: $proposedDate,
                            proposedByName: $participant->name,
                        ),
                    );
                });
        }

        return $proposedDate;
    }

    public function deleteProposedDate(Participant $participant, ProposedDate $proposedDate): void
    {
        $round = $this->activeRound();

        abort_unless($round->isPolling(), 403, 'Les propositions de dates ne sont pas ouvertes pour le moment.');
        abort_unless($proposedDate->scheduling_round_id === $round->id, 404);
        abort_unless($proposedDate->proposed_by_participant_id === $participant->id, 403);

        $proposedDate->delete();
    }

    /**
     * @param  array<int, string>  $votes
     */
    public function storeVotes(Participant $participant, array $votes): void
    {
        $round = $this->activeRound();

        DB::transaction(function () use ($participant, $votes, $round): void {
            foreach ($votes as $proposedDateId => $availability) {
                $proposedDate = ProposedDate::query()
                    ->whereKey($proposedDateId)
                    ->where('scheduling_round_id', $round->id)
                    ->first();

                if (! $proposedDate instanceof ProposedDate) {
                    continue;
                }

                if ($round->isConfirmed()) {
                    $availability = in_array($availability, [Availability::Yes->value, Availability::No->value], true)
                        ? $availability
                        : Availability::No->value;
                }

                Vote::query()->updateOrCreate(
                    [
                        'participant_id' => $participant->id,
                        'proposed_date_id' => $proposedDate->id,
                    ],
                    [
                        'availability' => $availability,
                    ],
                );
            }

            if ($round->isPolling()) {
                $this->attemptConfirmation($round->fresh());
            }
        });
    }

    public function attemptConfirmation(SchedulingRound $round): void
    {
        if (! $round->isPolling()) {
            return;
        }

        $threshold = config('poker.min_participants');

        $winningDate = ProposedDate::query()
            ->where('scheduling_round_id', $round->id)
            ->withCount([
                'votes as yes_count' => fn ($query) => $query->where('availability', Availability::Yes),
            ])
            ->get()
            ->filter(fn (ProposedDate $date): bool => $date->yes_count >= $threshold)
            ->sortBy([
                ['yes_count', 'desc'],
                ['starts_at', 'asc'],
            ])
            ->first();

        if (! $winningDate instanceof ProposedDate) {
            return;
        }

        $round->update([
            'status' => SchedulingRoundStatus::Confirmed,
            'confirmed_proposed_date_id' => $winningDate->id,
        ]);

        Participant::query()->each(function (Participant $participant) use ($winningDate): void {
            Mail::to($participant->email)->send(new TournamentConfirmedMail($participant, $winningDate));
        });
    }

    public function completePastTournaments(): int
    {
        $completed = 0;

        SchedulingRound::query()
            ->where('status', SchedulingRoundStatus::Confirmed)
            ->whereHas('confirmedDate', fn ($query) => $query->where('starts_at', '<', now()))
            ->with('confirmedDate')
            ->each(function (SchedulingRound $round) use (&$completed): void {
                $round->update(['status' => SchedulingRoundStatus::Completed]);

                $newRound = SchedulingRound::query()->create([
                    'status' => SchedulingRoundStatus::Polling,
                ]);

                Participant::query()->each(function (Participant $participant) use ($newRound): void {
                    Mail::to($participant->email)->send(new NewPollOpenedMail($participant, $newRound));
                });

                $completed++;
            });

        return $completed;
    }

    /**
     * @return array<string, mixed>
     */
    public function historyData(?Participant $participant): array
    {
        $this->completePastTournaments();

        $pastNights = SchedulingRound::query()
            ->where('status', SchedulingRoundStatus::Completed)
            ->whereNotNull('confirmed_proposed_date_id')
            ->with(['confirmedDate.votes.participant'])
            ->get()
            ->sortByDesc(fn (SchedulingRound $round): string => $round->confirmedDate?->starts_at?->toIso8601String() ?? '')
            ->values()
            ->map(function (SchedulingRound $round): array {
                $confirmedDate = $round->confirmedDate;

                return [
                    'id' => $round->id,
                    'startsAt' => $confirmedDate->starts_at->toIso8601String(),
                    'label' => $confirmedDate->starts_at
                        ->locale('fr')
                        ->translatedFormat('l j F Y \à H\hi'),
                    'location' => $confirmedDate->location,
                    'theme' => $confirmedDate->theme,
                    'attendingCount' => $confirmedDate->votes
                        ->where('availability', Availability::Yes)
                        ->count(),
                    'attendingNames' => $this->voterNames($confirmedDate, Availability::Yes),
                    'declinedNames' => $this->voterNames($confirmedDate, Availability::No),
                ];
            })
            ->all();

        return [
            'pastNights' => $pastNights,
            'participant' => $participant ? [
                'id' => $participant->id,
                'name' => $participant->name,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pageData(?Participant $participant): array
    {
        $this->completePastTournaments();

        $round = $this->activeRound()->load([
            'proposedDates' => fn ($query) => $query
                ->orderBy('starts_at')
                ->with(['votes.participant']),
            'confirmedDate.votes.participant',
        ]);

        $participantVotes = $participant instanceof Participant
            ? Vote::query()
                ->where('participant_id', $participant->id)
                ->whereIn('proposed_date_id', $round->proposedDates->pluck('id'))
                ->pluck('availability', 'proposed_date_id')
            : collect();

        return [
            'round' => [
                'id' => $round->id,
                'status' => $round->status->value,
                'minParticipants' => config('poker.min_participants'),
                'confirmedDate' => $round->confirmedDate ? [
                    'id' => $round->confirmedDate->id,
                    'startsAt' => $round->confirmedDate->starts_at->toIso8601String(),
                    'label' => $round->confirmedDate->starts_at
                        ->locale('fr')
                        ->translatedFormat('l j F Y \à H\hi'),
                    'location' => $round->confirmedDate->location,
                    'theme' => $round->confirmedDate->theme,
                    'attendingCount' => $round->confirmedDate->votes
                        ->where('availability', Availability::Yes)
                        ->count(),
                    'attendingNames' => $this->voterNames($round->confirmedDate, Availability::Yes),
                    'declinedNames' => $this->voterNames($round->confirmedDate, Availability::No),
                ] : null,
                'dates' => $round->proposedDates->map(function (ProposedDate $date) use ($participantVotes, $round, $participant): array {
                    $yesCount = $date->votes->where('availability', Availability::Yes)->count();
                    $maybeCount = $date->votes->where('availability', Availability::Maybe)->count();

                    return [
                        'id' => $date->id,
                        'startsAt' => $date->starts_at->toIso8601String(),
                        'label' => $date->starts_at
                            ->locale('fr')
                            ->translatedFormat('l j F Y \à H\hi'),
                        'location' => $date->location,
                        'theme' => $date->theme,
                        'yesCount' => $yesCount,
                        'maybeCount' => $maybeCount,
                        'yesNames' => $this->voterNames($date, Availability::Yes),
                        'maybeNames' => $this->voterNames($date, Availability::Maybe),
                        'noNames' => $this->voterNames($date, Availability::No),
                        'reachedThreshold' => $yesCount >= config('poker.min_participants'),
                        'myVote' => $round->isConfirmed() && $round->confirmed_proposed_date_id !== $date->id
                            ? null
                            : ($participantVotes->get($date->id)?->value ?? null),
                        'isConfirmed' => $round->confirmed_proposed_date_id === $date->id,
                        'canDelete' => $round->isPolling()
                            && $participant instanceof Participant
                            && $date->proposed_by_participant_id === $participant->id,
                    ];
                })->values()->all(),
            ],
            'participant' => $participant ? [
                'id' => $participant->id,
                'name' => $participant->name,
            ] : null,
            'participants' => Participant::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Participant $participant): array => [
                    'id' => $participant->id,
                    'name' => $participant->name,
                ])
                ->all(),
            'subscribedCount' => Participant::query()->count(),
        ];
    }

    /**
     * @return list<string>
     */
    private function voterNames(ProposedDate $date, Availability $availability): array
    {
        return $date->votes
            ->where('availability', $availability)
            ->map(fn (Vote $vote): string => $vote->participant->name)
            ->sort()
            ->values()
            ->all();
    }
}
