<?php

namespace App\Mail;

use App\Models\Participant;
use App\Models\ProposedDate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TournamentsConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, ProposedDate>  $proposedDates
     */
    public function __construct(
        public Participant $participant,
        public Collection $proposedDates,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $count = $this->proposedDates->count();

        return new Envelope(
            subject: $count === 1
                ? 'C’est calé ! Prochaine Poker party'
                : "{$count} soirées poker viennent d’être calées",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.poker.confirmed',
            with: [
                'name' => $this->participant->name,
                'dates' => $this->proposedDates
                    ->map(fn (ProposedDate $date): array => $this->dateSummary($date))
                    ->values()
                    ->all(),
                'url' => route('home', ['token' => $this->participant->token]),
            ],
        );
    }

    /**
     * @return array{label: string, location: string|null, theme: string|null, beginnersWelcome: bool}
     */
    private function dateSummary(ProposedDate $date): array
    {
        return [
            'label' => $date->starts_at
                ->locale('fr')
                ->translatedFormat('l j F Y \à H\hi'),
            'location' => $date->location ?: config('poker.location'),
            'theme' => $date->theme,
            'beginnersWelcome' => $date->beginners_welcome,
        ];
    }
}
