<?php

namespace App\Mail;

use App\Models\Participant;
use App\Models\ProposedDate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewProposedDateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Participant $participant,
        public ProposedDate $proposedDate,
        public string $proposedByName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle date proposée — Poker party',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.poker.new-date',
            with: [
                'name' => $this->participant->name,
                'proposedByName' => $this->proposedByName,
                'dateLabel' => $this->proposedDate->starts_at
                    ->locale('fr')
                    ->translatedFormat('l j F Y \à H\hi'),
                'location' => $this->proposedDate->location,
                'theme' => $this->proposedDate->theme,
                'url' => route('home', ['token' => $this->participant->token]),
            ],
        );
    }
}
