<?php

namespace App\Mail;

use App\Models\Participant;
use App\Models\SchedulingRound;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewPollOpenedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Participant $participant,
        public SchedulingRound $schedulingRound,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'On relance une soirée poker ?',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.poker.new-poll',
            with: [
                'name' => $this->participant->name,
                'url' => route('home', ['token' => $this->participant->token]),
            ],
        );
    }
}
