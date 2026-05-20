<?php

namespace App\Mail;

use App\Models\Participant;
use App\Models\ProposedDate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TournamentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Participant $participant,
        public ProposedDate $proposedDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'C’est calé ! Prochaine Poker party',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.poker.confirmed',
            with: [
                'name' => $this->participant->name,
                'dateLabel' => $this->proposedDate->starts_at
                    ->locale('fr')
                    ->translatedFormat('l j F Y \à H\hi'),
                'location' => config('poker.location'),
                'url' => route('home', ['token' => $this->participant->token]),
            ],
        );
    }
}
