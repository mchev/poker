<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\SchedulingRoundStatus;
use App\Mail\AdminParticipantSubscribedMail;
use App\Mail\NewProposedDateMail;
use App\Mail\ParticipantWelcomeMail;
use App\Mail\TournamentsConfirmedMail;
use App\Mail\VoteReminderMail;
use App\Models\Game;
use App\Models\Participant;
use App\Models\ProposedDate;
use App\Models\SchedulingRound;
use App\Models\Vote;
use App\Support\PokerAdmin;
use App\Support\PokerMailDispatcher;
use App\Support\ProposedDateCalendar;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PokerSchedulingService
{
    public function __construct(private BrevoContactService $brevoContacts) {}

    public function activeRound(): SchedulingRound
    {
        $round = SchedulingRound::query()
            ->where('status', SchedulingRoundStatus::Polling)
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
        $email = Participant::normalizeEmail($email);

        $participant = Participant::query()->firstWhere('email', $email);

        if ($participant) {
            $participant->update(['name' => $name]);
        } else {
            $participant = Participant::query()->create([
                'name' => $name,
                'email' => $email,
            ]);
        }

        PokerMailDispatcher::queueToParticipant($participant, new ParticipantWelcomeMail($participant));
        PokerMailDispatcher::queue(
            config('mail.from.address'),
            new AdminParticipantSubscribedMail($participant),
            redirectInLocal: false,
        );

        $this->brevoContacts->syncParticipant($participant);

        return $participant;
    }

    public function sendAccessLink(Participant $participant): void
    {
        PokerMailDispatcher::queueToParticipant($participant, new ParticipantWelcomeMail($participant));
    }

    public function findParticipantByEmail(string $email): ?Participant
    {
        return Participant::query()->firstWhere(
            'email',
            Participant::normalizeEmail($email),
        );
    }

    public function findParticipantByToken(?string $token): ?Participant
    {
        if (blank($token)) {
            return null;
        }

        return Participant::query()->where('token', $token)->first();
    }

    public function proposeDate(
        Participant $participant,
        Carbon $startsAt,
        string $location,
        ?string $theme = null,
        bool $beginnersWelcome = true,
        ?array $gameIds = null,
    ): ProposedDate {
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
                'beginners_welcome' => $beginnersWelcome,
            ],
        );

        if (filled($gameIds)) {
            $proposedDate->games()->sync($gameIds);
        }

        if ($proposedDate->wasRecentlyCreated) {
            $proposedDate->load('games');

            Participant::query()
                ->whereKeyNot($participant->id)
                ->where(function ($query) use ($proposedDate): void {
                    if ($proposedDate->games->isNotEmpty()) {
                        $query->whereHas('gamePreferences', function ($q) use ($proposedDate): void {
                            $q->whereIn('game_id', $proposedDate->games->pluck('id'));
                        }, '>=', 1)
                            ->orWhereDoesntHave('gamePreferences');
                    }
                })
                ->each(function (Participant $recipient) use ($proposedDate, $participant): void {
                    PokerMailDispatcher::queueToParticipant(
                        $recipient,
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

    public function canParticipantEditProposedDate(Participant $participant, ProposedDate $proposedDate): bool
    {
        $round = $this->activeRound();

        if ($proposedDate->scheduling_round_id !== $round->id || ! $round->isPolling()) {
            return false;
        }

        return PokerAdmin::isAdmin($participant)
            || $proposedDate->proposed_by_participant_id === $participant->id;
    }

    public function canParticipantEditNote(Participant $participant, ProposedDate $proposedDate): bool
    {
        $round = $this->activeRound();

        return $round->isPolling()
            && $proposedDate->scheduling_round_id === $round->id
            && $proposedDate->isConfirmed()
            && (PokerAdmin::isAdmin($participant)
                || $proposedDate->proposed_by_participant_id === $participant->id);
    }

    public function updateParticipantName(Participant $participant, string $name): Participant
    {
        $participant->update(['name' => trim($name)]);

        $this->brevoContacts->syncParticipant($participant->fresh());

        return $participant;
    }

    /**
     * @param  array<int>  $gameIds
     */
    public function updateGamePreferences(Participant $participant, array $gameIds): Participant
    {
        $participant->gamePreferences()->sync($gameIds);

        return $participant;
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string, icon: string|null}>
     */
    public function availableGames(): array
    {
        return Game::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Game $game): array => $this->gamePayload($game))
            ->all();
    }

    /**
     * @param  array{location?: string, note?: string|null, game_ids?: array<int>|null, starts_at?: Carbon}  $updates
     */
    public function updateProposedDate(Participant $participant, ProposedDate $proposedDate, array $updates): ProposedDate
    {
        abort_unless($this->canParticipantEditProposedDate($participant, $proposedDate), 403);

        if (array_key_exists('note', $updates)) {
            abort_unless($this->canParticipantEditNote($participant, $proposedDate), 403);

            $proposedDate->note = filled($updates['note']) ? trim($updates['note']) : null;
        }

        if (array_key_exists('location', $updates)) {
            $proposedDate->location = $updates['location'];
        }

        if (array_key_exists('game_ids', $updates)) {
            $proposedDate->games()->sync($updates['game_ids'] ?? []);
        }

        if (array_key_exists('starts_at', $updates)) {
            $this->validateUniqueStartsAt($proposedDate, $updates['starts_at']);

            $proposedDate->starts_at = $updates['starts_at'];
        }

        $proposedDate->save();

        return $proposedDate;
    }

    private function validateUniqueStartsAt(ProposedDate $proposedDate, Carbon $startsAt): void
    {
        $existing = ProposedDate::where('scheduling_round_id', $proposedDate->scheduling_round_id)
            ->where('id', '!=', $proposedDate->id)
            ->where('starts_at', $startsAt)
            ->exists();

        abort_if($existing, 422, 'Une autre date proposée existe déjà avec ce créneau horaire.');
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

                if ($proposedDate->isConfirmed()) {
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

        $newlyConfirmedDates = ProposedDate::query()
            ->where('scheduling_round_id', $round->id)
            ->whereNull('confirmed_at')
            ->withCount([
                'votes as yes_count' => fn ($query) => $query->where('availability', Availability::Yes),
            ])
            ->get()
            ->filter(fn (ProposedDate $date): bool => $date->yes_count >= $threshold)
            ->sortBy([
                ['yes_count', 'desc'],
                ['starts_at', 'asc'],
            ]);

        $confirmedDates = collect();

        foreach ($newlyConfirmedDates as $winningDate) {
            $winningDate->update(['confirmed_at' => now()]);
            $confirmedDates->push($winningDate->fresh());

            if ($round->confirmed_proposed_date_id === null) {
                $round->update(['confirmed_proposed_date_id' => $winningDate->id]);
            }
        }

        if ($confirmedDates->isNotEmpty()) {
            $participantCount = Participant::query()->count();

            Log::info('Poker dates confirmed, dispatching confirmation emails.', [
                'round_id' => $round->id,
                'date_ids' => $confirmedDates->pluck('id')->all(),
                'participant_count' => $participantCount,
                'queue_connection' => config('queue.default'),
            ]);

            Participant::query()->each(function (Participant $participant) use ($confirmedDates): void {
                PokerMailDispatcher::queueToParticipant(
                    $participant,
                    new TournamentsConfirmedMail($participant, $confirmedDates),
                );
            });
        }
    }

    public function remindNonVoters(Participant $actor, ProposedDate $proposedDate): int
    {
        $round = $this->activeRound();

        abort_unless($round->isPolling(), 403);
        abort_unless(! $proposedDate->isConfirmed(), 403);
        abort_unless($proposedDate->scheduling_round_id === $round->id, 404);

        $sent = $this->dispatchVoteRemindersForDate($proposedDate, manual: true);

        Log::info('Poker manual vote reminder triggered.', [
            'proposed_date_id' => $proposedDate->id,
            'triggered_by_participant_id' => $actor->id,
            'sent_count' => $sent,
        ]);

        return $sent;
    }

    public function resendConfirmationMail(Participant $target): void
    {
        $confirmedDates = $this->upcomingConfirmedDates();

        abort_if($confirmedDates->isEmpty(), 422, 'Aucune soirée calée à annoncer.');

        PokerMailDispatcher::queueToParticipant(
            $target,
            new TournamentsConfirmedMail($target, $confirmedDates),
        );
    }

    public function resendConfirmationMailsToAll(): int
    {
        $confirmedDates = $this->upcomingConfirmedDates();

        abort_if($confirmedDates->isEmpty(), 422, 'Aucune soirée calée à annoncer.');

        $sent = 0;

        Participant::query()->each(function (Participant $participant) use ($confirmedDates, &$sent): void {
            PokerMailDispatcher::queueToParticipant(
                $participant,
                new TournamentsConfirmedMail($participant, $confirmedDates),
            );

            $sent++;
        });

        Log::info('Poker admin resent confirmation emails to all participants.', [
            'participant_count' => $sent,
            'date_ids' => $confirmedDates->pluck('id')->all(),
        ]);

        return $sent;
    }

    public function deleteParticipant(Participant $target): void
    {
        $target->delete();
    }

    /**
     * @return Collection<int, ProposedDate>
     */
    private function upcomingConfirmedDates(): Collection
    {
        return $this->activeRound()
            ->proposedDates()
            ->whereNotNull('confirmed_at')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->get();
    }

    public function completePastTournaments(): int
    {
        $completed = 0;

        SchedulingRound::query()
            ->where('status', SchedulingRoundStatus::Polling)
            ->whereHas('proposedDates', function ($query): void {
                $query
                    ->whereNotNull('confirmed_at')
                    ->where('starts_at', '<', now());
            })
            ->with('proposedDates')
            ->each(function (SchedulingRound $round) use (&$completed): void {
                DB::transaction(function () use ($round, &$completed): void {
                    $pastConfirmed = $round->proposedDates
                        ->filter(fn (ProposedDate $date): bool => $date->isConfirmed() && $date->starts_at->isPast())
                        ->sortByDesc('starts_at');

                    if ($pastConfirmed->isEmpty()) {
                        return;
                    }

                    $archivedDate = $pastConfirmed->first();

                    $round->update([
                        'status' => SchedulingRoundStatus::Completed,
                        'confirmed_proposed_date_id' => $archivedDate->id,
                    ]);

                    $datesToCarryOver = $round->proposedDates
                        ->reject(fn (ProposedDate $date): bool => $date->isConfirmed() && $date->starts_at->isPast());

                    if ($datesToCarryOver->isEmpty()) {
                        $this->activeRound();
                        $completed++;

                        return;
                    }

                    $newRound = SchedulingRound::query()->create([
                        'status' => SchedulingRoundStatus::Polling,
                    ]);

                    ProposedDate::query()
                        ->whereIn('id', $datesToCarryOver->pluck('id'))
                        ->update(['scheduling_round_id' => $newRound->id]);

                    $completed++;
                });
            });

        return $completed;
    }

    public function sendVoteReminders(): int
    {
        $round = $this->activeRound();

        if (! $round->isPolling()) {
            return 0;
        }

        $threshold = config('poker.min_participants');
        $tomorrow = Carbon::now()->timezone(config('app.timezone'))->addDay()->toDateString();

        $dates = ProposedDate::query()
            ->where('scheduling_round_id', $round->id)
            ->whereNull('confirmed_at')
            ->whereNull('vote_reminder_sent_at')
            ->whereDate('starts_at', $tomorrow)
            ->with(['votes'])
            ->withCount([
                'votes as yes_count' => fn ($query) => $query->where('availability', Availability::Yes),
            ])
            ->get()
            ->filter(fn (ProposedDate $date): bool => $date->yes_count < $threshold);

        $sent = 0;

        foreach ($dates as $date) {
            $sent += $this->dispatchVoteRemindersForDate($date);
            $date->update(['vote_reminder_sent_at' => now()]);
        }

        if ($sent > 0) {
            Log::info('Poker automatic vote reminders dispatched.', [
                'sent_count' => $sent,
                'round_id' => $round->id,
            ]);
        }

        return $sent;
    }

    private function dispatchVoteRemindersForDate(ProposedDate $proposedDate, bool $manual = false): int
    {
        $proposedDate->loadMissing('votes');

        $threshold = config('poker.min_participants');
        $yesCount = $proposedDate->votes->where('availability', Availability::Yes)->count();
        $votedParticipantIds = $proposedDate->votes->pluck('participant_id');

        $sent = 0;

        Participant::query()
            ->whereNotIn('id', $votedParticipantIds)
            ->each(function (Participant $participant) use ($proposedDate, $threshold, $yesCount, $manual, &$sent): void {
                PokerMailDispatcher::queueToParticipant(
                    $participant,
                    new VoteReminderMail(
                        participant: $participant,
                        proposedDate: $proposedDate,
                        yesCount: $yesCount,
                        threshold: $threshold,
                        manual: $manual,
                    ),
                );

                $sent++;
            });

        return $sent;
    }

    /**
     * @return array<string, mixed>
     */
    public function historyData(?Participant $participant): array
    {
        $this->completePastTournaments();

        $pastNights = ProposedDate::query()
            ->whereNotNull('confirmed_at')
            ->where('starts_at', '<', now())
            ->with(['votes.participant', 'winner', 'games'])
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (ProposedDate $confirmedDate): array => [
                'id' => $confirmedDate->id,
                'startsAt' => $confirmedDate->starts_at->toIso8601String(),
                'label' => $this->humanDateLabel($confirmedDate->starts_at),
                'location' => $confirmedDate->location,
                'theme' => $confirmedDate->theme,
                'beginnersWelcome' => $confirmedDate->beginners_welcome,
                'note' => $confirmedDate->note,
                'games' => $confirmedDate->games->map(fn (Game $g): array => $this->gamePayload($g))->values()->all(),
                'attendingCount' => $confirmedDate->votes
                    ->where('availability', Availability::Yes)
                    ->count(),
                'attendees' => $confirmedDate->votes
                    ->where('availability', Availability::Yes)
                    ->map(fn (Vote $vote): array => [
                        'id' => $vote->participant_id,
                        'name' => $vote->participant->name,
                    ])
                    ->sortBy('name')
                    ->values()
                    ->all(),
                'winnerParticipantId' => $confirmedDate->winner_participant_id,
                'winnerName' => $confirmedDate->winner?->name,
            ])
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
    public function adminData(?Participant $participant): array
    {
        $this->completePastTournaments();

        $round = $this->activeRound();

        return [
            'participant' => $participant ? [
                'id' => $participant->id,
                'name' => $participant->name,
                'isAdmin' => PokerAdmin::isAdmin($participant),
            ] : null,
            'adminParticipants' => $participant && PokerAdmin::isAdmin($participant)
                ? Participant::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'email'])
                    ->map(fn (Participant $listed): array => [
                        'id' => $listed->id,
                        'name' => $listed->name,
                        'email' => $listed->email,
                    ])
                    ->all()
                : [],
            'hasConfirmedDates' => $round->proposedDates()->whereNotNull('confirmed_at')->exists(),
            'subscribedCount' => Participant::query()->count(),
        ];
    }

    public function setPastNightWinner(ProposedDate $proposedDate, ?int $winnerParticipantId): ProposedDate
    {
        abort_unless($proposedDate->isConfirmed(), 404);
        abort_unless($proposedDate->starts_at->isPast(), 403, 'Le gagnant ne peut être choisi que pour une soirée passée.');

        $proposedDate->update(['winner_participant_id' => $winnerParticipantId]);

        return $proposedDate->fresh();
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
                ->with(['votes.participant', 'games']),
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
                'confirmedDates' => $round->proposedDates
                    ->filter(fn (ProposedDate $date): bool => $date->isConfirmed())
                    ->map(fn (ProposedDate $date): array => $this->confirmedDatePayload($date, $participant, $participantVotes))
                    ->values()
                    ->all(),
                'dates' => $round->proposedDates
                    ->filter(fn (ProposedDate $date): bool => ! $date->isConfirmed())
                    ->map(function (ProposedDate $date) use ($participantVotes, $round, $participant): array {
                        $yesCount = $date->votes->where('availability', Availability::Yes)->count();
                        $maybeCount = $date->votes->where('availability', Availability::Maybe)->count();
                        $votedCount = $date->votes->pluck('participant_id')->unique()->count();
                        $nonVoterCount = max(0, Participant::query()->count() - $votedCount);

                        return [
                            'id' => $date->id,
                            'startsAt' => $date->starts_at->toIso8601String(),
                            'label' => $this->humanDateLabel($date->starts_at),
                            'location' => $date->location,
                            'theme' => $date->theme,
                            'beginnersWelcome' => $date->beginners_welcome,
                            'note' => $date->note,
                            'games' => $date->games->map(fn (Game $g): array => $this->gamePayload($g))->values()->all(),
                            'canEditLocation' => $participant instanceof Participant
                                && $this->canParticipantEditProposedDate($participant, $date),
                            'canEditTime' => $participant instanceof Participant
                                && $this->canParticipantEditProposedDate($participant, $date),
                            'yesCount' => $yesCount,
                            'maybeCount' => $maybeCount,
                            'yesNames' => $this->voterNames($date, Availability::Yes),
                            'maybeNames' => $this->voterNames($date, Availability::Maybe),
                            'noNames' => $this->voterNames($date, Availability::No),
                            'reachedThreshold' => $yesCount >= config('poker.min_participants'),
                            'myVote' => $participantVotes->get($date->id)?->value ?? null,
                            'isConfirmed' => false,
                            'canDelete' => $round->isPolling()
                                && $participant instanceof Participant
                                && $date->proposed_by_participant_id === $participant->id,
                            'nonVoterCount' => $nonVoterCount,
                            'canRemindNonVoters' => $round->isPolling()
                                && $participant instanceof Participant
                                && $nonVoterCount > 0,
                        ];
                    })
                    ->sort(function (array $a, array $b): int {
                        $byVotes = $b['yesCount'] <=> $a['yesCount'];

                        return $byVotes !== 0 ? $byVotes : $a['startsAt'] <=> $b['startsAt'];
                    })
                    ->values()
                    ->all(),
            ],
            'participant' => $participant ? [
                'id' => $participant->id,
                'name' => $participant->name,
                'isAdmin' => PokerAdmin::isAdmin($participant),
            ] : null,
            'adminParticipants' => $participant && PokerAdmin::isAdmin($participant)
                ? Participant::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'email'])
                    ->map(fn (Participant $listed): array => [
                        'id' => $listed->id,
                        'name' => $listed->name,
                        'email' => $listed->email,
                    ])
                    ->all()
                : [],
            'participants' => Participant::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Participant $participant): array => [
                    'id' => $participant->id,
                    'name' => $participant->name,
                ])
                ->all(),
            'subscribedCount' => Participant::query()->count(),
            'personalUrl' => $participant
                ? route('home', ['token' => $participant->token])
                : null,
            'availableGames' => $this->availableGames(),
            'participantGamePreferences' => $participant instanceof Participant
                ? $participant->gamePreferences->pluck('id')->all()
                : [],
        ];
    }

    /**
     * @param  Collection<int, Availability>  $participantVotes
     * @return array<string, mixed>
     */
    private function confirmedDatePayload(
        ProposedDate $date,
        ?Participant $participant,
        Collection $participantVotes,
    ): array {
        return [
            'id' => $date->id,
            'startsAt' => $date->starts_at->toIso8601String(),
            'label' => $this->humanDateLabel($date->starts_at),
            'location' => $date->location,
            'theme' => $date->theme,
            'beginnersWelcome' => $date->beginners_welcome,
            'note' => $date->note,
            'games' => $date->games->map(fn (Game $g): array => $this->gamePayload($g))->values()->all(),
            'myVote' => $participantVotes->get($date->id)?->value,
            'canEditLocation' => $participant instanceof Participant
                && $this->canParticipantEditProposedDate($participant, $date),
            'canEditNote' => $participant instanceof Participant
                && $this->canParticipantEditNote($participant, $date),
            'attendingCount' => $date->votes
                ->where('availability', Availability::Yes)
                ->count(),
            'attendingNames' => $this->voterNames($date, Availability::Yes),
            'declinedNames' => $this->voterNames($date, Availability::No),
            'calendarIcsUrl' => route('poker.dates.calendar', $date),
            'googleCalendarUrl' => ProposedDateCalendar::googleCalendarUrl($date),
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

    /**
     * @return array{id: int, name: string, slug: string, icon: string|null}
     */
    private function gamePayload(Game $game): array
    {
        return [
            'id' => $game->id,
            'name' => $game->name,
            'slug' => $game->slug,
            'icon' => $game->icon,
        ];
    }

    private function humanDateLabel(CarbonInterface $startsAt): string
    {
        $datePart = $startsAt->locale('fr')->translatedFormat('l j F');
        $timePart = $startsAt->minute === 0
            ? $startsAt->format('G').'h'
            : $startsAt->format('G').'h'.$startsAt->format('i');

        return 'le '.$datePart.' à '.$timePart;
    }
}
