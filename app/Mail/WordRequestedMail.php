<?php

namespace App\Mail;

use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WordRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $word,
        public string $language,
        public User $requester,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Word requested: {$this->word} ({$this->language})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.word-requested',
        );
    }
}
