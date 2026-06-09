<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResendAccessLinkRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\StorePastNightWinnerRequest;
use App\Http\Requests\StoreProposedDateRequest;
use App\Http\Requests\StoreVotesRequest;
use App\Http\Requests\SubscribeParticipantRequest;
use App\Http\Requests\UpdateProposedDateRequest;
use App\Models\Participant;
use App\Models\ProposedDate;
use App\Services\PokerSchedulingService;
use App\Support\ProposedDateCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PokerController extends Controller
{
    public function __construct(private PokerSchedulingService $scheduling) {}

    public function index(Request $request): Response
    {
        /** @var Participant|null $participant */
        $participant = $request->attributes->get('poker_participant');

        return Inertia::render('Poker/Index', $this->scheduling->pageData($participant));
    }

    public function history(Request $request): Response
    {
        /** @var Participant|null $participant */
        $participant = $request->attributes->get('poker_participant');

        return Inertia::render('Poker/History', $this->scheduling->historyData($participant));
    }

    public function subscribe(SubscribeParticipantRequest $request): RedirectResponse
    {
        $participant = $this->scheduling->subscribe(
            $request->validated('name'),
            $request->validated('email'),
        );

        return redirect()
            ->route('home', ['token' => $participant->token])
            ->with('toast', [
                'type' => 'success',
                'message' => 'C’est bon ! Check tes mails, ton lien t’attend. Tu peux voter tout de suite.',
            ]);
    }

    public function storeVotes(StoreVotesRequest $request): RedirectResponse
    {
        /** @var Participant $participant */
        $participant = $request->participant();

        $this->scheduling->storeVotes($participant, $request->validated('votes'));

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Noté ! Tes dispos sont enregistrées.',
        ]);
    }

    public function storeProposedDate(StoreProposedDateRequest $request): RedirectResponse
    {
        /** @var Participant $participant */
        $participant = $request->participant();

        $startsAt = Carbon::createFromFormat(
            'Y-m-d H:i',
            $request->validated('date').' '.$request->validated('time'),
            config('app.timezone'),
        );

        $this->scheduling->proposeDate(
            $participant,
            $startsAt,
            $request->locationLabel(),
            $request->validated('theme'),
            $request->boolean('beginners_welcome'),
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Date ajoutée ! Les autres peuvent voter.',
        ]);
    }

    public function updateProposedDate(UpdateProposedDateRequest $request, ProposedDate $proposedDate): RedirectResponse
    {
        /** @var Participant $participant */
        $participant = $request->participant();

        $updates = [];

        if ($request->has('location_type')) {
            $updates['location'] = $request->locationLabel();
        }

        if ($request->has('note')) {
            $updates['note'] = $request->validated('note');
        }

        $this->scheduling->updateProposedDate($participant, $proposedDate, $updates);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'C’est enregistré.',
        ]);
    }

    public function destroyProposedDate(Request $request, ProposedDate $proposedDate): RedirectResponse
    {
        /** @var Participant|null $participant */
        $participant = $request->attributes->get('poker_participant');

        abort_unless($participant instanceof Participant, 403);

        $this->scheduling->deleteProposedDate($participant, $proposedDate);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Créneau supprimé.',
        ]);
    }

    public function storeAttendance(StoreAttendanceRequest $request): RedirectResponse
    {
        /** @var Participant $participant */
        $participant = $request->participant();

        $proposedDate = ProposedDate::query()->findOrFail($request->validated('proposed_date_id'));

        abort_unless($proposedDate->isConfirmed(), 403);
        abort_unless(
            $proposedDate->scheduling_round_id === $this->scheduling->activeRound()->id,
            404,
        );

        $this->scheduling->storeVotes($participant, [
            $proposedDate->id => $request->validated('attending'),
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Merci, c’est noté !',
        ]);
    }

    public function remindNonVoters(Request $request, ProposedDate $proposedDate): RedirectResponse
    {
        /** @var Participant|null $participant */
        $participant = $request->attributes->get('poker_participant');

        abort_unless($participant instanceof Participant, 403);

        $sent = $this->scheduling->remindNonVoters($participant, $proposedDate);

        return back()->with('toast', [
            'type' => 'success',
            'message' => $sent > 0
                ? "Relance envoyée à {$sent} personne".($sent > 1 ? 's' : '').'.'
                : 'Tout le monde a déjà voté pour ce créneau.',
        ]);
    }

    public function resendAccessLink(ResendAccessLinkRequest $request): RedirectResponse
    {
        /** @var Participant $participant */
        $participant = $request->participant();

        $this->scheduling->sendAccessLink($participant);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Lien renvoyé ! Jette un œil à ta boîte mail.',
        ]);
    }

    public function updatePastNightWinner(
        StorePastNightWinnerRequest $request,
        ProposedDate $proposedDate,
    ): RedirectResponse {
        $this->scheduling->setPastNightWinner(
            $proposedDate,
            $request->validated('winner_participant_id'),
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Gagnant enregistré.',
        ]);
    }

    public function calendar(ProposedDate $proposedDate): HttpResponse
    {
        abort_unless($proposedDate->isConfirmed(), 404);

        return response(
            ProposedDateCalendar::icsContent($proposedDate),
            200,
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="poker-party.ics"',
            ],
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        return redirect()
            ->route('home')
            ->withoutCookie(config('poker.cookie_name'))
            ->with('toast', [
                'type' => 'success',
                'message' => 'À bientôt ! Tu peux revenir via ton lien mail quand tu veux.',
            ]);
    }
}
