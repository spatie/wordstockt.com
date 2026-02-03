<?php

namespace App\Mail;

use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WordReportedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Dictionary $dictionary,
        public User $reporter,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Word reported: {$this->dictionary->word} ({$this->dictionary->language})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.word-reported',
        );
    }
}
