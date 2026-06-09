<?php

use App\Enums\Availability;
use App\Enums\SchedulingRoundStatus;
use App\Mail\AdminParticipantSubscribedMail;
use App\Mail\NewPollOpenedMail;
use App\Mail\NewProposedDateMail;
use App\Mail\ParticipantWelcomeMail;
use App\Mail\TournamentsConfirmedMail;
use App\Mail\VoteReminderMail;
use App\Models\Participant;
use App\Models\ProposedDate;
use App\Models\SchedulingRound;
use App\Models\Vote;
use App\Services\BrevoContactService;
use App\Services\PokerSchedulingService;
use App\Support\PokerMailDispatcher;
use App\Support\ProposedDateCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

test('proposed dates keep the local time entered in the form', function () {
    $participant = Participant::factory()->create();
    SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);

    $this->withCookie(config('poker.cookie_name'), $participant->token)
        ->post(route('poker.dates.store', ['token' => $participant->token]), [
            'date' => '2026-09-18',
            'time' => '18:00',
            'location_type' => 'fabrique',
        ])
        ->assertRedirect();

    $label = app(PokerSchedulingService::class)
        ->pageData($participant)['round']['dates'][0]['label'];

    expect($label)->toBe('vendredi 18 septembre 2026 à 18h00');
});

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

test('history page renders completed poker nights without exposing emails', function () {
    $round = SchedulingRound::factory()->completed()->create();
    $confirmedDate = ProposedDate::factory()->create([
        'scheduling_round_id' => $round->id,
        'starts_at' => CarbonImmutable::parse('2026-03-15 20:00:00'),
        'confirmed_at' => CarbonImmutable::parse('2026-03-10 12:00:00'),
        'proposed_by_participant_id' => null,
    ]);

    $round->update(['confirmed_proposed_date_id' => $confirmedDate->id]);

    $attending = Participant::factory()->create(['name' => 'Alex']);
    $declined = Participant::factory()->create(['name' => 'Marie']);

    Vote::factory()->create([
        'participant_id' => $attending->id,
        'proposed_date_id' => $confirmedDate->id,
        'availability' => Availability::Yes,
    ]);

    Vote::factory()->create([
        'participant_id' => $declined->id,
        'proposed_date_id' => $confirmedDate->id,
        'availability' => Availability::No,
    ]);

    $this->get(route('poker.history'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Poker/History')
            ->has('pastNights', 1)
            ->where('pastNights.0.label', 'dimanche 15 mars 2026 à 20h00')
            ->where('pastNights.0.attendees', [['id' => $attending->id, 'name' => 'Alex']])
            ->missing('pastNights.0.declinedNames')
            ->missing('pastNights.0.attendees.0.email'));
});

test('logged-in participants can mark the winner of a past night', function () {
    $round = SchedulingRound::factory()->completed()->create();
    $pastDate = ProposedDate::factory()->create([
        'scheduling_round_id' => $round->id,
        'starts_at' => now()->subWeek(),
        'confirmed_at' => now()->subWeeks(2),
        'proposed_by_participant_id' => null,
    ]);

    $winner = Participant::factory()->create(['name' => 'Alex']);
    $other = Participant::factory()->create(['name' => 'Marie']);
    $voter = Participant::factory()->create();

    foreach ([$winner, $other] as $participant) {
        Vote::factory()->create([
            'participant_id' => $participant->id,
            'proposed_date_id' => $pastDate->id,
            'availability' => Availability::Yes,
        ]);
    }

    $this->withCookie(config('poker.cookie_name'), $voter->token)
        ->patch(route('poker.history.winner.update', [
            'proposedDate' => $pastDate,
            'token' => $voter->token,
        ]), [
            'winner_participant_id' => $winner->id,
        ])
        ->assertRedirect();

    expect($pastDate->fresh()->winner_participant_id)->toBe($winner->id);

    $data = app(PokerSchedulingService::class)->historyData($voter);

    expect($data['pastNights'][0]['winnerName'])->toBe('Alex');
});

test('winner must be among attendees of a past night', function () {
    $round = SchedulingRound::factory()->completed()->create();
    $pastDate = ProposedDate::factory()->create([
        'scheduling_round_id' => $round->id,
        'starts_at' => now()->subWeek(),
        'confirmed_at' => now()->subWeeks(2),
        'proposed_by_participant_id' => null,
    ]);

    $attending = Participant::factory()->create();
    $absent = Participant::factory()->create();
    $voter = Participant::factory()->create();

    Vote::factory()->create([
        'participant_id' => $attending->id,
        'proposed_date_id' => $pastDate->id,
        'availability' => Availability::Yes,
    ]);

    Vote::factory()->create([
        'participant_id' => $absent->id,
        'proposed_date_id' => $pastDate->id,
        'availability' => Availability::No,
    ]);

    $this->withCookie(config('poker.cookie_name'), $voter->token)
        ->patch(route('poker.history.winner.update', [
            'proposedDate' => $pastDate,
            'token' => $voter->token,
        ]), [
            'winner_participant_id' => $absent->id,
        ])
        ->assertSessionHasErrors('winner_participant_id');
});

test('history page shows an empty state when no nights are completed', function () {
    SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);

    $this->get(route('poker.history'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Poker/History')
            ->has('pastNights', 0));
});

test('re-subscribing with the same email keeps token and votes', function () {
    Mail::fake();
    config(['mail.from.address' => 'admin@example.com']);

    $participant = Participant::factory()->create([
        'email' => 'alex@example.com',
        'name' => 'Alex',
    ]);
    $originalToken = $participant->token;

    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $proposedDate = ProposedDate::factory()->create([
        'scheduling_round_id' => $round->id,
        'proposed_by_participant_id' => $participant->id,
    ]);

    Vote::factory()->create([
        'participant_id' => $participant->id,
        'proposed_date_id' => $proposedDate->id,
        'availability' => Availability::Yes,
    ]);

    $this->post(route('poker.subscribe'), [
        'name' => 'Alexandre',
        'email' => 'ALEX@example.com',
    ])->assertRedirect(route('home', ['token' => $originalToken]));

    $participant->refresh();

    expect($participant->token)->toBe($originalToken)
        ->and($participant->name)->toBe('Alexandre')
        ->and(Participant::query()->count())->toBe(1)
        ->and(Vote::query()->where('participant_id', $participant->id)->count())->toBe(1);

    Mail::assertQueued(ParticipantWelcomeMail::class, fn ($mail) => $mail->hasTo('alex@example.com'));
});

test('participants can subscribe and receive a personal link', function () {
    Mail::fake();
    config(['mail.from.address' => 'admin@example.com']);

    $response = $this->post(route('poker.subscribe'), [
        'name' => 'Alex',
        'email' => 'alex@example.com',
    ]);

    $participant = Participant::query()->where('email', 'alex@example.com')->first();

    expect($participant)->not->toBeNull()
        ->and($participant->name)->toBe('Alex');

    $response->assertRedirect(route('home', ['token' => $participant->token]));

    Mail::assertQueued(ParticipantWelcomeMail::class, fn ($mail) => $mail->hasTo('alex@example.com'));
    Mail::assertQueued(AdminParticipantSubscribedMail::class, fn ($mail) => $mail->hasTo('admin@example.com'));
});

test('participants receive an email when a new date is proposed', function () {
    Mail::fake();

    $participants = Participant::factory()->count(3)->create();
    $proposer = $participants->first();

    SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);

    $this->withCookie(config('poker.cookie_name'), $proposer->token)
        ->post(route('poker.dates.store', ['token' => $proposer->token]), [
            'date' => '2026-10-02',
            'time' => '20:00',
            'location_type' => 'fabrique',
        ])
        ->assertRedirect();

    Mail::assertQueued(NewProposedDateMail::class, 2);
    Mail::assertNotQueued(NewProposedDateMail::class, fn ($mail) => $mail->hasTo($proposer->email));
});

test('proposed dates welcome beginners by default', function () {
    Mail::fake();

    $proposer = Participant::factory()->create();

    SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);

    $this->withCookie(config('poker.cookie_name'), $proposer->token)
        ->post(route('poker.dates.store', ['token' => $proposer->token]), [
            'date' => '2026-11-21',
            'time' => '20:00',
            'location_type' => 'fabrique',
        ])
        ->assertRedirect();

    $data = app(PokerSchedulingService::class)->pageData($proposer);

    expect(ProposedDate::query()->first()->beginners_welcome)->toBeTrue()
        ->and($data['round']['dates'][0]['beginnersWelcome'])->toBeTrue();
});

test('participants can opt out of welcoming beginners on a proposed date', function () {
    Mail::fake();

    $proposer = Participant::factory()->create();

    SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);

    $this->withCookie(config('poker.cookie_name'), $proposer->token)
        ->post(route('poker.dates.store', ['token' => $proposer->token]), [
            'date' => '2026-11-22',
            'time' => '20:00',
            'location_type' => 'fabrique',
            'beginners_welcome' => '0',
        ])
        ->assertRedirect();

    expect(ProposedDate::query()->first()->beginners_welcome)->toBeFalse();
});

test('participants can choose a location when proposing a date', function () {
    Mail::fake();

    $proposer = Participant::factory()->create(['name' => 'Alex']);
    $host = Participant::factory()->create(['name' => 'Marie']);

    SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);

    $this->withCookie(config('poker.cookie_name'), $proposer->token)
        ->post(route('poker.dates.store', ['token' => $proposer->token]), [
            'date' => '2026-10-09',
            'time' => '20:00',
            'location_type' => 'member',
            'location_participant_id' => $host->id,
            'theme' => 'Soirée débutants',
        ])
        ->assertRedirect();

    $data = app(PokerSchedulingService::class)->pageData($proposer);

    expect(ProposedDate::query()->first()->location)->toBe('Chez Marie')
        ->and(ProposedDate::query()->first()->theme)->toBe('Soirée débutants')
        ->and(ProposedDate::query()->first()->beginners_welcome)->toBeTrue()
        ->and($data['round']['dates'][0]['location'])->toBe('Chez Marie')
        ->and($data['round']['dates'][0]['theme'])->toBe('Soirée débutants')
        ->and($data['round']['dates'][0]['canDelete'])->toBeTrue()
        ->and($data['participants'])->toContain([
            'id' => $proposer->id,
            'name' => 'Alex',
        ], [
            'id' => $host->id,
            'name' => 'Marie',
        ]);
});

test('participants can update location while polling', function () {
    $participant = Participant::factory()->create(['name' => 'Alex']);
    $host = Participant::factory()->create(['name' => 'Marie']);
    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $proposedDate = ProposedDate::factory()
        ->for($round)
        ->create([
            'location' => 'La fabrique',
            'proposed_by_participant_id' => $participant->id,
        ]);

    $this->withCookie(config('poker.cookie_name'), $participant->token)
        ->patch(route('poker.dates.update', [
            'proposedDate' => $proposedDate,
            'token' => $participant->token,
        ]), [
            'location_type' => 'member',
            'location_participant_id' => $host->id,
        ])
        ->assertRedirect();

    expect($proposedDate->fresh()->location)->toBe('Chez Marie');
});

test('participants can update location and note on confirmed date', function () {
    $participant = Participant::factory()->create(['name' => 'Alex']);
    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $proposedDate = ProposedDate::factory()
        ->for($round)
        ->create([
            'location' => 'La fabrique',
            'confirmed_at' => now(),
            'proposed_by_participant_id' => $participant->id,
        ]);
    $round->update(['confirmed_proposed_date_id' => $proposedDate->id]);

    $this->withCookie(config('poker.cookie_name'), $participant->token)
        ->patch(route('poker.dates.update', [
            'proposedDate' => $proposedDate,
            'token' => $participant->token,
        ]), [
            'location_type' => 'mine',
        ])
        ->assertRedirect();

    expect($proposedDate->fresh()->location)->toBe('Chez Alex');

    $this->withCookie(config('poker.cookie_name'), $participant->token)
        ->patch(route('poker.dates.update', [
            'proposedDate' => $proposedDate,
            'token' => $participant->token,
        ]), [
            'note' => 'Apporter des chips.',
        ])
        ->assertRedirect();

    expect($proposedDate->fresh()->note)->toBe('Apporter des chips.');
});

test('participants cannot add a note before the date is confirmed', function () {
    $participant = Participant::factory()->create();
    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $proposedDate = ProposedDate::factory()
        ->for($round)
        ->create([
            'proposed_by_participant_id' => $participant->id,
        ]);

    $this->withCookie(config('poker.cookie_name'), $participant->token)
        ->patch(route('poker.dates.update', $proposedDate), [
            'note' => 'Trop tôt.',
        ])
        ->assertForbidden();
});

test('participants can delete their own proposed date while polling', function () {
    Mail::fake();

    $participant = Participant::factory()->create();
    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $proposedDate = ProposedDate::factory()->create([
        'scheduling_round_id' => $round->id,
        'proposed_by_participant_id' => $participant->id,
    ]);

    Vote::factory()->create([
        'participant_id' => $participant->id,
        'proposed_date_id' => $proposedDate->id,
        'availability' => Availability::Yes,
    ]);

    $this->withCookie(config('poker.cookie_name'), $participant->token)
        ->delete(route('poker.dates.destroy', [
            'token' => $participant->token,
            'proposedDate' => $proposedDate,
        ]))
        ->assertRedirect()
        ->assertSessionHas('toast.message', 'Créneau supprimé.');

    expect(ProposedDate::query()->whereKey($proposedDate->id)->exists())->toBeFalse()
        ->and(Vote::query()->where('proposed_date_id', $proposedDate->id)->exists())->toBeFalse();
});

test('participants cannot delete another participant proposed date', function () {
    $owner = Participant::factory()->create();
    $otherParticipant = Participant::factory()->create();
    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $proposedDate = ProposedDate::factory()->create([
        'scheduling_round_id' => $round->id,
        'proposed_by_participant_id' => $owner->id,
    ]);

    $this->withCookie(config('poker.cookie_name'), $otherParticipant->token)
        ->delete(route('poker.dates.destroy', [
            'token' => $otherParticipant->token,
            'proposedDate' => $proposedDate,
        ]))
        ->assertForbidden();

    expect(ProposedDate::query()->whereKey($proposedDate->id)->exists())->toBeTrue();
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
            'location_type' => 'mine',
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
        ->status->toBe(SchedulingRoundStatus::Polling)
        ->confirmed_proposed_date_id->toBe($proposedDate->id);

    expect($proposedDate->fresh()->confirmed_at)->not->toBeNull();

    Mail::assertQueued(TournamentsConfirmedMail::class, 2);
    Mail::assertQueued(
        TournamentsConfirmedMail::class,
        fn (TournamentsConfirmedMail $mail) => $mail->proposedDates->count() === 1,
    );
});

test('multiple dates confirmed in one batch send a single digest email per participant', function () {
    Mail::fake();

    config(['poker.min_participants' => 2]);

    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $firstDate = ProposedDate::factory()->for($round)->create([
        'starts_at' => now()->addWeek(),
        'proposed_by_participant_id' => null,
    ]);
    $secondDate = ProposedDate::factory()->for($round)->create([
        'starts_at' => now()->addWeeks(2),
        'proposed_by_participant_id' => null,
    ]);

    $voters = Participant::factory()->count(2)->create();

    foreach ($voters as $participant) {
        foreach ([$firstDate, $secondDate] as $date) {
            Vote::factory()->create([
                'participant_id' => $participant->id,
                'proposed_date_id' => $date->id,
                'availability' => Availability::Yes,
            ]);
        }
    }

    app(PokerSchedulingService::class)->attemptConfirmation($round->fresh());

    Mail::assertQueued(TournamentsConfirmedMail::class, 2);
    Mail::assertQueued(
        TournamentsConfirmedMail::class,
        fn (TournamentsConfirmedMail $mail) => $mail->proposedDates->count() === 2,
    );
});

test('poll dates are sorted by yes count descending then start time', function () {
    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $popular = ProposedDate::factory()->for($round)->create([
        'starts_at' => now()->addWeeks(3),
        'proposed_by_participant_id' => null,
    ]);
    $soon = ProposedDate::factory()->for($round)->create([
        'starts_at' => now()->addWeek(),
        'proposed_by_participant_id' => null,
    ]);

    $voters = Participant::factory()->count(3)->create();

    foreach ($voters->take(2) as $participant) {
        Vote::factory()->create([
            'participant_id' => $participant->id,
            'proposed_date_id' => $popular->id,
            'availability' => Availability::Yes,
        ]);
    }

    Vote::factory()->create([
        'participant_id' => $voters->last()->id,
        'proposed_date_id' => $soon->id,
        'availability' => Availability::Yes,
    ]);

    $data = app(PokerSchedulingService::class)->pageData(null);

    expect($data['round']['dates'][0]['id'])->toBe($popular->id)
        ->and($data['round']['dates'][1]['id'])->toBe($soon->id);
});

test('confirmed dates expose calendar export urls', function () {
    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $confirmed = ProposedDate::factory()->for($round)->create([
        'starts_at' => now()->addWeek(),
        'confirmed_at' => now(),
    ]);

    $data = app(PokerSchedulingService::class)->pageData(null);

    expect($data['round']['confirmedDates'][0]['calendarIcsUrl'])
        ->toBe(route('poker.dates.calendar', $confirmed))
        ->and($data['round']['confirmedDates'][0]['googleCalendarUrl'])
        ->toContain('calendar.google.com');

    $this->get(route('poker.dates.calendar', $confirmed))
        ->assertOk()
        ->assertHeader('content-type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VCALENDAR', false);
});

test('calendar ics includes beginners welcome in the description', function () {
    $date = ProposedDate::factory()->create([
        'starts_at' => now()->addWeek(),
        'beginners_welcome' => true,
    ]);

    expect(ProposedDateCalendar::icsContent($date))->toContain('Débutant');
});

test('new proposed date emails mention beginners welcome', function () {
    Mail::fake();

    $proposer = Participant::factory()->create();
    $recipient = Participant::factory()->create();
    SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);

    $this->withCookie(config('poker.cookie_name'), $proposer->token)
        ->post(route('poker.dates.store', ['token' => $proposer->token]), [
            'date' => now()->addWeeks(2)->format('Y-m-d'),
            'time' => '20:00',
            'location_type' => 'fabrique',
            'beginners_welcome' => '1',
        ])
        ->assertRedirect();

    Mail::assertQueued(NewProposedDateMail::class, function (NewProposedDateMail $mail) use ($recipient) {
        return $mail->hasTo($recipient->email)
            && $mail->proposedDate->beginners_welcome;
    });
});

test('local environment sends participant mail synchronously', function () {
    Mail::fake();

    app()->detectEnvironment(fn () => 'local');

    $participant = Participant::factory()->create();

    PokerMailDispatcher::queueToParticipant($participant, new ParticipantWelcomeMail($participant));

    Mail::assertSent(ParticipantWelcomeMail::class, fn ($mail) => $mail->hasTo('martin@pegase.io'));
    Mail::assertNotQueued(ParticipantWelcomeMail::class);

    app()->detectEnvironment(fn () => 'testing');
});

test('local environment redirects participant mail to the safe inbox', function () {
    app()->detectEnvironment(fn () => 'local');
    config([
        'poker.redirect_mail_in_local' => true,
        'poker.local_mail_redirect' => 'martin@pegase.io',
    ]);

    expect(PokerMailDispatcher::resolveRecipient('player@example.com'))->toBe('martin@pegase.io');

    app()->detectEnvironment(fn () => 'testing');
});

test('brevo contact sync is skipped in local environment', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['brevo.api_key' => 'test-api-key', 'brevo.list_id' => 65]);

    Http::fake();

    $participant = Participant::factory()->create();

    app(BrevoContactService::class)->syncParticipant($participant);

    Http::assertNothingSent();

    app()->detectEnvironment(fn () => 'testing');
});

test('confirming one date keeps the poll open for other proposed dates', function () {
    Mail::fake();

    config(['poker.min_participants' => 2]);

    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $confirmedDate = ProposedDate::factory()->for($round)->create([
        'starts_at' => now()->addWeek(),
    ]);
    $otherDate = ProposedDate::factory()->for($round)->create([
        'starts_at' => now()->addWeeks(2),
    ]);

    $voters = Participant::factory()->count(2)->create();

    foreach ($voters as $participant) {
        Vote::factory()->create([
            'participant_id' => $participant->id,
            'proposed_date_id' => $confirmedDate->id,
            'availability' => Availability::Yes,
        ]);
    }

    app(PokerSchedulingService::class)->attemptConfirmation($round->fresh());

    $voter = Participant::factory()->create();

    $this->withCookie(config('poker.cookie_name'), $voter->token)
        ->post(route('poker.votes.store', ['token' => $voter->token]), [
            'votes' => [$otherDate->id => Availability::Maybe->value],
        ])
        ->assertRedirect();

    $data = app(PokerSchedulingService::class)->pageData($voter);

    expect($round->fresh()->status)->toBe(SchedulingRoundStatus::Polling)
        ->and($data['round']['confirmedDates'])->toHaveCount(1)
        ->and($data['round']['confirmedDates'][0]['id'])->toBe($confirmedDate->id)
        ->and($data['round']['dates'])->toHaveCount(1)
        ->and($data['round']['dates'][0]['id'])->toBe($otherDate->id)
        ->and($data['round']['dates'][0]['myVote'])->toBe(Availability::Maybe->value);
});

test('participants can propose a new date while another date is already confirmed', function () {
    Mail::fake();

    $participant = Participant::factory()->create();
    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    ProposedDate::factory()->for($round)->create([
        'starts_at' => now()->addWeek(),
        'confirmed_at' => now(),
    ]);

    $this->withCookie(config('poker.cookie_name'), $participant->token)
        ->post(route('poker.dates.store', ['token' => $participant->token]), [
            'date' => '2026-11-14',
            'time' => '20:00',
            'location_type' => 'fabrique',
        ])
        ->assertRedirect();

    expect(ProposedDate::query()->where('scheduling_round_id', $round->id)->count())->toBe(2);
});

test('past confirmed sessions close the round and keep future dates in the poll', function () {
    Mail::fake();

    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $pastConfirmed = ProposedDate::factory()->for($round)->create([
        'starts_at' => now()->subDay(),
        'confirmed_at' => now()->subWeek(),
        'proposed_by_participant_id' => null,
    ]);
    $futureDate = ProposedDate::factory()->for($round)->create([
        'starts_at' => now()->addWeek(),
        'proposed_by_participant_id' => null,
    ]);

    app(PokerSchedulingService::class)->completePastTournaments();

    expect($round->fresh())
        ->status->toBe(SchedulingRoundStatus::Completed)
        ->confirmed_proposed_date_id->toBe($pastConfirmed->id);

    $activeRound = SchedulingRound::query()
        ->where('status', SchedulingRoundStatus::Polling)
        ->sole();

    expect($activeRound->id)->not->toBe($round->id)
        ->and($futureDate->fresh()->scheduling_round_id)->toBe($activeRound->id)
        ->and($pastConfirmed->fresh()->scheduling_round_id)->toBe($round->id);

    Mail::assertNotSent(NewPollOpenedMail::class);
});

test('past confirmed sessions appear in history', function () {
    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Completed]);
    $pastConfirmed = ProposedDate::factory()->for($round)->create([
        'starts_at' => now()->subDays(3),
        'confirmed_at' => now()->subWeek(),
    ]);

    $data = app(PokerSchedulingService::class)->historyData(null);

    expect($data['pastNights'])->toHaveCount(1)
        ->and($data['pastNights'][0]['id'])->toBe($pastConfirmed->id);
});

test('vote reminders are sent the day before undersubscribed poll dates to non-voters', function () {
    Mail::fake();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-08 10:00:00', 'Europe/Paris'));

    config(['poker.min_participants' => 3]);

    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $tomorrowDate = ProposedDate::factory()->for($round)->create([
        'starts_at' => CarbonImmutable::parse('2026-06-09 20:00:00', 'Europe/Paris'),
        'proposed_by_participant_id' => null,
    ]);

    $voter = Participant::factory()->create();
    Participant::factory()->count(2)->create();

    Vote::factory()->create([
        'participant_id' => $voter->id,
        'proposed_date_id' => $tomorrowDate->id,
        'availability' => Availability::Yes,
    ]);

    $sent = app(PokerSchedulingService::class)->sendVoteReminders();

    expect($sent)->toBe(2)
        ->and($tomorrowDate->fresh()->vote_reminder_sent_at)->not->toBeNull();

    Mail::assertQueued(VoteReminderMail::class, 2);
    Mail::assertNotQueued(VoteReminderMail::class, fn (VoteReminderMail $mail) => $mail->hasTo($voter->email));

    CarbonImmutable::setTestNow();
});

test('vote reminders are not sent when the threshold is already reached', function () {
    Mail::fake();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-08 10:00:00', 'Europe/Paris'));

    config(['poker.min_participants' => 3]);

    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $tomorrowDate = ProposedDate::factory()->for($round)->create([
        'starts_at' => CarbonImmutable::parse('2026-06-09 20:00:00', 'Europe/Paris'),
        'proposed_by_participant_id' => null,
    ]);

    $participants = Participant::factory()->count(3)->create();

    foreach ($participants as $participant) {
        Vote::factory()->create([
            'participant_id' => $participant->id,
            'proposed_date_id' => $tomorrowDate->id,
            'availability' => Availability::Yes,
        ]);
    }

    Participant::factory()->create();

    expect(app(PokerSchedulingService::class)->sendVoteReminders())->toBe(0);

    Mail::assertNothingQueued();
    expect($tomorrowDate->fresh()->vote_reminder_sent_at)->toBeNull();

    CarbonImmutable::setTestNow();
});

test('vote reminders are only sent once per poll date', function () {
    Mail::fake();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-08 10:00:00', 'Europe/Paris'));

    config(['poker.min_participants' => 3]);

    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $tomorrowDate = ProposedDate::factory()->for($round)->create([
        'starts_at' => CarbonImmutable::parse('2026-06-09 20:00:00', 'Europe/Paris'),
        'vote_reminder_sent_at' => now(),
        'proposed_by_participant_id' => null,
    ]);

    Participant::factory()->count(2)->create();

    expect(app(PokerSchedulingService::class)->sendVoteReminders())->toBe(0);

    Mail::assertNothingQueued();

    CarbonImmutable::setTestNow();
});

test('participants can manually remind non-voters for a poll date', function () {
    Mail::fake();

    config(['poker.min_participants' => 3]);

    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $pollDate = ProposedDate::factory()->for($round)->create([
        'starts_at' => CarbonImmutable::parse('2026-06-20 20:00:00', 'Europe/Paris'),
        'proposed_by_participant_id' => null,
    ]);

    $actor = Participant::factory()->create();
    $voter = Participant::factory()->create();
    Participant::factory()->count(2)->create();

    Vote::factory()->create([
        'participant_id' => $voter->id,
        'proposed_date_id' => $pollDate->id,
        'availability' => Availability::Yes,
    ]);

    $this->withCookie(config('poker.cookie_name'), $actor->token)
        ->post(route('poker.dates.remind', [
            'token' => $actor->token,
            'proposedDate' => $pollDate,
        ]))
        ->assertRedirect()
        ->assertSessionHas('toast.message', 'Relance envoyée à 3 personnes.');

    Mail::assertQueued(VoteReminderMail::class, 3);
    Mail::assertQueued(
        VoteReminderMail::class,
        fn (VoteReminderMail $mail) => $mail->manual === true,
    );
    Mail::assertNotQueued(VoteReminderMail::class, fn (VoteReminderMail $mail) => $mail->hasTo($voter->email));
});

test('manual vote reminders are not sent for confirmed dates', function () {
    Mail::fake();

    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $confirmedDate = ProposedDate::factory()->for($round)->create([
        'confirmed_at' => now(),
        'proposed_by_participant_id' => null,
    ]);

    $actor = Participant::factory()->create();

    $this->withCookie(config('poker.cookie_name'), $actor->token)
        ->post(route('poker.dates.remind', [
            'token' => $actor->token,
            'proposedDate' => $confirmedDate,
        ]))
        ->assertForbidden();

    Mail::assertNothingQueued();
});

test('guests cannot manually remind non-voters', function () {
    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $pollDate = ProposedDate::factory()->for($round)->create([
        'proposed_by_participant_id' => null,
    ]);

    $this->post(route('poker.dates.remind', $pollDate))
        ->assertForbidden();
});

test('poker mail dispatch is logged when queuing confirmation emails', function () {
    Log::spy();
    Mail::fake();

    config(['poker.min_participants' => 2]);

    $round = SchedulingRound::factory()->create(['status' => SchedulingRoundStatus::Polling]);
    $pollDate = ProposedDate::factory()->for($round)->create([
        'proposed_by_participant_id' => null,
    ]);

    $participants = Participant::factory()->count(2)->create();

    foreach ($participants as $participant) {
        Vote::factory()->create([
            'participant_id' => $participant->id,
            'proposed_date_id' => $pollDate->id,
            'availability' => Availability::Yes,
        ]);
    }

    app(PokerSchedulingService::class)->attemptConfirmation($round->fresh());

    Log::shouldHaveReceived('info')
        ->with('Poker dates confirmed, dispatching confirmation emails.', Mockery::on(
            fn (array $context): bool => $context['round_id'] === $round->id
                && $context['participant_count'] === 2
                && in_array($pollDate->id, $context['date_ids'], true),
        ));
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

    Mail::assertQueued(ParticipantWelcomeMail::class, fn ($mail) => $mail->hasTo($participant->email));
});
