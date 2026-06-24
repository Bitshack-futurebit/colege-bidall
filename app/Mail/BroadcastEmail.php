<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BroadcastEmail extends Mailable
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $broadcastSubject,
        public string $broadcastMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->broadcastSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.broadcast',
        );
    }
}
