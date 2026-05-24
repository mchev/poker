<?php

namespace App\Mail;

use App\Models\Participant;
use App\Models\SchedulingRound;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewPollOpenedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Participant $participant,
        public SchedulingRound $schedulingRound,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'On relance une Poker party ?',
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
