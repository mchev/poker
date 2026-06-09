<?php

namespace Database\Seeders;

use App\Enums\Availability;
use App\Enums\SchedulingRoundStatus;
use App\Models\Participant;
use App\Models\ProposedDate;
use App\Models\SchedulingRound;
use App\Models\Vote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PokerDemoSeeder extends Seeder
{
    public const string DEMO_TOKEN = 'local-demo-martin000000000000000000000000000000000000000000000000000000';

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->error('PokerDemoSeeder ne s’exécute qu’en environnement local.');

            return;
        }

        $this->wipePokerData();

        $participants = $this->createParticipants();
        $martin = $participants['Martin'];

        $this->seedHistory($participants);
        $this->seedActiveRound($participants, $martin);

        $this->command?->newLine();
        $this->command?->info('Données de démo poker créées.');
        $this->command?->line('Connecte-toi en tant que Martin (organisateur de plusieurs créneaux) :');
        $this->command?->line(route('home', ['token' => self::DEMO_TOKEN]));
        $this->command?->newLine();
        $this->command?->line('Autres comptes de test :');
        foreach ($participants as $name => $participant) {
            if ($name === 'Martin') {
                continue;
            }

            $this->command?->line("  {$name} — ".route('home', ['token' => $participant->token]));
        }
    }

    private function wipePokerData(): void
    {
        Vote::query()->delete();
        SchedulingRound::query()->update(['confirmed_proposed_date_id' => null]);
        ProposedDate::query()->delete();
        SchedulingRound::query()->delete();
        Participant::query()->delete();
    }

    /**
     * @return array<string, Participant>
     */
    private function createParticipants(): array
    {
        $roster = [
            'Martin' => 'martin@pegase.io',
            'Alex' => 'alex.demo@poker.test',
            'Marie' => 'marie.demo@poker.test',
            'Julien' => 'julien.demo@poker.test',
            'Camille' => 'camille.demo@poker.test',
            'Thomas' => 'thomas.demo@poker.test',
            'Léa' => 'lea.demo@poker.test',
            'Hugo' => 'hugo.demo@poker.test',
            'Chloé' => 'chloe.demo@poker.test',
            'Nicolas' => 'nicolas.demo@poker.test',
            'Sarah' => 'sarah.demo@poker.test',
            'Antoine' => 'antoine.demo@poker.test',
        ];

        $participants = [];

        foreach ($roster as $name => $email) {
            $attributes = [
                'name' => $name,
                'email' => $email,
            ];

            if ($name === 'Martin') {
                $attributes['token'] = self::DEMO_TOKEN;
            }

            $participants[$name] = Participant::query()->create($attributes);
        }

        return $participants;
    }

    /**
     * @param  array<string, Participant>  $participants
     */
    private function seedHistory(array $participants): void
    {
        $round = SchedulingRound::query()->create([
            'status' => SchedulingRoundStatus::Completed,
        ]);

        $pastDate = ProposedDate::query()->create([
            'scheduling_round_id' => $round->id,
            'starts_at' => Carbon::now()->subMonths(2)->subDays(3)->setTime(20, 0),
            'location' => 'La fabrique',
            'theme' => 'Soirée du printemps',
            'beginners_welcome' => true,
            'note' => 'On avait fini vers minuit, super ambiance.',
            'confirmed_at' => Carbon::now()->subMonths(2),
            'proposed_by_participant_id' => $participants['Alex']->id,
        ]);

        $round->update(['confirmed_proposed_date_id' => $pastDate->id]);

        $pastDate->update(['winner_participant_id' => $participants['Julien']->id]);

        foreach (['Alex', 'Marie', 'Julien', 'Camille', 'Thomas', 'Léa'] as $name) {
            $this->vote($participants[$name], $pastDate, Availability::Yes);
        }

        foreach (['Hugo', 'Chloé'] as $name) {
            $this->vote($participants[$name], $pastDate, Availability::No);
        }
    }

    /**
     * @param  array<string, Participant>  $participants
     */
    private function seedActiveRound(array $participants, Participant $martin): void
    {
        $round = SchedulingRound::query()->create([
            'status' => SchedulingRoundStatus::Polling,
        ]);

        $confirmedPopular = ProposedDate::query()->create([
            'scheduling_round_id' => $round->id,
            'starts_at' => Carbon::now()->addDays(9)->setTime(20, 0),
            'location' => 'La fabrique',
            'theme' => null,
            'beginners_welcome' => true,
            'note' => 'Apporter des chips si possible — on complète sur place sinon.',
            'confirmed_at' => Carbon::now()->subDay(),
            'proposed_by_participant_id' => $martin->id,
        ]);

        $confirmedSmall = ProposedDate::query()->create([
            'scheduling_round_id' => $round->id,
            'starts_at' => Carbon::now()->addDays(16)->setTime(20, 30),
            'location' => 'Chez Alex',
            'theme' => 'Table serrée',
            'beginners_welcome' => false,
            'note' => null,
            'confirmed_at' => Carbon::now()->subHours(6),
            'proposed_by_participant_id' => $participants['Alex']->id,
        ]);

        $round->update(['confirmed_proposed_date_id' => $confirmedPopular->id]);

        foreach (['Martin', 'Alex', 'Marie', 'Julien', 'Camille', 'Thomas', 'Nicolas'] as $name) {
            $this->vote($participants[$name], $confirmedPopular, Availability::Yes);
        }

        $this->vote($participants['Sarah'], $confirmedPopular, Availability::No);

        foreach (['Alex', 'Marie', 'Julien', 'Camille'] as $name) {
            $this->vote($participants[$name], $confirmedSmall, Availability::Yes);
        }

        $this->vote($martin, $confirmedSmall, Availability::Maybe);

        $pollAlmost = ProposedDate::query()->create([
            'scheduling_round_id' => $round->id,
            'starts_at' => Carbon::now()->addDays(12)->setTime(19, 30),
            'location' => 'Chez Marie',
            'theme' => 'Pot-limit Omaha',
            'beginners_welcome' => false,
            'proposed_by_participant_id' => $participants['Marie']->id,
        ]);

        foreach (['Martin', 'Marie', 'Julien'] as $name) {
            $this->vote($participants[$name], $pollAlmost, Availability::Yes);
        }

        $this->vote($participants['Camille'], $pollAlmost, Availability::Maybe);
        $this->vote($participants['Thomas'], $pollAlmost, Availability::Maybe);
        $this->vote($participants['Hugo'], $pollAlmost, Availability::No);

        $pollBeginners = ProposedDate::query()->create([
            'scheduling_round_id' => $round->id,
            'starts_at' => Carbon::now()->addDays(20)->setTime(20, 0),
            'location' => 'La fabrique',
            'theme' => 'Initiation',
            'beginners_welcome' => true,
            'proposed_by_participant_id' => $participants['Léa']->id,
        ]);

        $this->vote($participants['Léa'], $pollBeginners, Availability::Yes);
        $this->vote($participants['Chloé'], $pollBeginners, Availability::Maybe);
        $this->vote($participants['Sarah'], $pollBeginners, Availability::Maybe);
        $this->vote($participants['Antoine'], $pollBeginners, Availability::Maybe);
        $this->vote($participants['Nicolas'], $pollBeginners, Availability::No);
        $this->vote($martin, $pollBeginners, Availability::Maybe);

        $pollEmpty = ProposedDate::query()->create([
            'scheduling_round_id' => $round->id,
            'starts_at' => Carbon::now()->addDays(25)->setTime(20, 0),
            'location' => 'Chez Hugo',
            'theme' => null,
            'beginners_welcome' => true,
            'proposed_by_participant_id' => $participants['Hugo']->id,
        ]);

        foreach (['Hugo', 'Chloé', 'Antoine'] as $name) {
            $this->vote($participants[$name], $pollEmpty, Availability::No);
        }
    }

    private function vote(Participant $participant, ProposedDate $date, Availability $availability): void
    {
        Vote::query()->create([
            'participant_id' => $participant->id,
            'proposed_date_id' => $date->id,
            'availability' => $availability,
        ]);
    }
}
