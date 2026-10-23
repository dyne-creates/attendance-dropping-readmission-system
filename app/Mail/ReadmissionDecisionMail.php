<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReadmissionDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public object $readmission,
        public string $outcome,
        public string $recipientRole = 'student',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Readmission decision: '.$this->decisionLabel(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.readmission-decision',
            with: [
                'decisionLabel' => $this->decisionLabel(),
            ],
        );
    }

    private function decisionLabel(): string
    {
        return match ($this->outcome) {
            'approved' => 'Approved',
            'excused' => 'Excused',
            'not_excused' => 'Not excused',
            default => 'Rejected',
        };
    }
}
