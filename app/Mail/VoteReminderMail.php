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

class VoteReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Participant $participant,
        public ProposedDate $proposedDate,
        public int $yesCount,
        public int $threshold,
        public bool $manual = false,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->manual
                ? 'Ton avis manque pour le prochain poker'
                : 'Demain c’est peut-être poker — ton avis manque encore',
        );
    }

    public function content(): Content
    {
        $missing = max(0, $this->threshold - $this->yesCount);

        return new Content(
            markdown: 'mail.poker.vote-reminder',
            with: [
                'name' => $this->participant->name,
                'dateLabel' => $this->proposedDate->starts_at
                    ->locale('fr')
                    ->translatedFormat('l j F Y \à H\hi'),
                'location' => $this->proposedDate->location,
                'theme' => $this->proposedDate->theme,
                'beginnersWelcome' => $this->proposedDate->beginners_welcome,
                'games' => $this->proposedDate->relationLoaded('games')
                    ? $this->proposedDate->games->pluck('name')->all()
                    : [],
                'yesCount' => $this->yesCount,
                'threshold' => $this->threshold,
                'missingCount' => $missing,
                'manual' => $this->manual,
                'url' => route('home', ['token' => $this->participant->token]),
            ],
        );
    }
}
