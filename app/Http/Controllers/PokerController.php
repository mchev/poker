<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResendAccessLinkRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\StoreProposedDateRequest;
use App\Http\Requests\StoreVotesRequest;
use App\Http\Requests\SubscribeParticipantRequest;
use App\Models\Participant;
use App\Services\PokerSchedulingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

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

        $this->scheduling->proposeDate($participant, $startsAt);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Date ajoutée ! Les autres peuvent voter.',
        ]);
    }

    public function storeAttendance(StoreAttendanceRequest $request): RedirectResponse
    {
        /** @var Participant $participant */
        $participant = $request->participant();

        $round = $this->scheduling->activeRound()->load('confirmedDate');

        if (! $round->isConfirmed() || ! $round->confirmedDate) {
            abort(403);
        }

        $this->scheduling->storeVotes($participant, [
            $round->confirmedDate->id => $request->validated('attending'),
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Merci, c’est noté !',
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
