<?php

namespace App\Mail;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminParticipantSubscribedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Participant $participant) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle inscription Poker party',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.poker.admin-participant-subscribed',
            with: [
                'participant' => $this->participant,
            ],
        );
    }
}
