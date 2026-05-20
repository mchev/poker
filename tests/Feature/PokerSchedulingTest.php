<?php

use App\Enums\Availability;
use App\Enums\SchedulingRoundStatus;
use App\Mail\NewPollOpenedMail;
use App\Mail\ParticipantWelcomeMail;
use App\Mail\TournamentConfirmedMail;
use App\Models\Participant;
use App\Models\ProposedDate;
use App\Models\SchedulingRound;
use App\Models\Vote;
use App\Services\PokerSchedulingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

test('dates are formatted in french', function () {
    CarbonImmutable::setLocale('fr');

    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    ProposedDate::factory()->create([
        'scheduling_round_id' => $round->id,
        'starts_at' => CarbonImmutable::parse('2026-06-12 20:00:00'),
        'proposed_by_participant_id' => null,
    ]);

    $data = app(PokerSchedulingService::class)->pageData(null);

    expect($data['round']['dates'][0]['label'])->toBe('vendredi 12 juin 2026 à 20h00');
});

test('home page renders poker scheduling screen', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Poker/Index')
            ->has('round')
            ->has('subscribedCount'));
});

test('participants can subscribe and receive a personal link', function () {
    Mail::fake();

    $response = $this->post(route('poker.subscribe'), [
        'name' => 'Alex',
        'email' => 'alex@example.com',
    ]);

    $participant = Participant::query()->where('email', 'alex@example.com')->first();

    expect($participant)->not->toBeNull()
        ->and($participant->name)->toBe('Alex');

    $response->assertRedirect(route('home', ['token' => $participant->token]));

    Mail::assertSent(ParticipantWelcomeMail::class, fn ($mail) => $mail->hasTo('alex@example.com'));
});

test('participants are synced to the Brevo contact list on subscribe', function () {
    Mail::fake();

    config([
        'brevo.api_key' => 'test-api-key',
        'brevo.list_id' => 65,
    ]);

    Http::fake([
        'api.brevo.com/v3/contacts' => Http::response(['id' => 123], 201),
    ]);

    $this->post(route('poker.subscribe'), [
        'name' => 'Marie',
        'email' => 'marie@example.com',
    ])->assertRedirect();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.brevo.com/v3/contacts'
            && $request->hasHeader('api-key', 'test-api-key')
            && $request['email'] === 'marie@example.com'
            && $request['attributes']['FNAME'] === 'Marie'
            && $request['listIds'] === [65]
            && $request['updateEnabled'] === true;
    });
});

test('subscribed participants can propose dates and vote without exposing emails', function () {
    Mail::fake();

    $participant = Participant::factory()->create();
    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);

    $this->withCookie(config('poker.cookie_name'), $participant->token)
        ->post(route('poker.dates.store', ['token' => $participant->token]), [
            'date' => now()->addWeek()->format('Y-m-d'),
            'time' => '20:00',
        ])
        ->assertRedirect();

    $proposedDate = ProposedDate::query()->first();

    expect($proposedDate)->not->toBeNull();

    $this->withCookie(config('poker.cookie_name'), $participant->token)
        ->post(route('poker.votes.store', ['token' => $participant->token]), [
            'votes' => [
                $proposedDate->id => Availability::Yes->value,
            ],
        ])
        ->assertRedirect();

    $this->withCookie(config('poker.cookie_name'), $participant->token)
        ->get(route('home', ['token' => $participant->token]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('participant.name', $participant->name)
            ->missing('participant.email')
            ->where('round.dates.0.yesCount', 1)
            ->where('round.dates.0.yesNames', [$participant->name]));
});

test('tournament is confirmed and emails are sent when threshold is reached', function () {
    Mail::fake();

    config(['poker.min_participants' => 2]);

    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $proposedDate = ProposedDate::factory()->create([
        'scheduling_round_id' => $round->id,
        'starts_at' => now()->addWeek(),
        'proposed_by_participant_id' => null,
    ]);

    $participants = Participant::factory()->count(2)->create();

    foreach ($participants as $participant) {
        Vote::factory()->create([
            'participant_id' => $participant->id,
            'proposed_date_id' => $proposedDate->id,
            'availability' => Availability::Yes,
        ]);
    }

    app(PokerSchedulingService::class)->attemptConfirmation($round->fresh());

    expect($round->fresh())
        ->status->toBe(SchedulingRoundStatus::Confirmed)
        ->confirmed_proposed_date_id->toBe($proposedDate->id);

    Mail::assertSent(TournamentConfirmedMail::class, 2);
});

test('past tournaments open a new poll and notify participants', function () {
    Mail::fake();

    $participants = Participant::factory()->count(2)->create();
    $round = SchedulingRound::factory()->confirmed()->create();
    $proposedDate = ProposedDate::factory()->create([
        'scheduling_round_id' => $round->id,
        'starts_at' => now()->subDay(),
        'proposed_by_participant_id' => null,
    ]);

    $round->update(['confirmed_proposed_date_id' => $proposedDate->id]);

    app(PokerSchedulingService::class)->completePastTournaments();

    expect($round->fresh()->status)->toBe(SchedulingRoundStatus::Completed);
    expect(SchedulingRound::query()->where('status', SchedulingRoundStatus::Polling)->count())->toBe(1);

    Mail::assertSent(NewPollOpenedMail::class, 2);
});

test('guests cannot vote without a personal token', function () {
    $this->post(route('poker.votes.store'), [
        'votes' => [1 => Availability::Yes->value],
    ])->assertForbidden();
});

test('participants can log out and lose access on this device', function () {
    $participant = Participant::factory()->create();

    $this->withCookie(config('poker.cookie_name'), $participant->token)
        ->post(route('poker.logout'))
        ->assertRedirect(route('home'));

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('participant', null));
});

test('participants can request a new access link by email', function () {
    Mail::fake();

    $participant = Participant::factory()->create();

    $this->withCookie(config('poker.cookie_name'), $participant->token)
        ->post(route('poker.access.resend', ['token' => $participant->token]))
        ->assertRedirect()
        ->assertSessionHas('toast.message', 'Lien renvoyé ! Jette un œil à ta boîte mail.');

    Mail::assertSent(ParticipantWelcomeMail::class, fn ($mail) => $mail->hasTo($participant->email));
});
