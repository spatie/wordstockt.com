<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WordRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $word,
        public string $language,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your word was not added: {$this->word}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.word-rejected',
        );
    }
}
