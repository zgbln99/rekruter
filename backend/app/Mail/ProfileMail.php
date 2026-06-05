<?php

namespace App\Mail;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfileMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Candidate $candidate,
        public string $pdfContent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Profil kandydata: '.$this->candidate->fullName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.profile',
            with: ['candidate' => $this->candidate],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $name = 'profil-'.str()->slug($this->candidate->fullName()).'.pdf';

        return [
            Attachment::fromData(fn () => $this->pdfContent, $name)
                ->withMime('application/pdf'),
        ];
    }
}
