<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PasswordChangeRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $type,
        public string $approveUrl,
        public string $rejectUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Knowledge Hub - Confirm '.ucfirst($this->type).' Change',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.password-change-request',
            with: [
                'user' => $this->user,
                'type' => $this->type,
                'approveUrl' => $this->approveUrl,
                'rejectUrl' => $this->rejectUrl,
                'expiresAt' => now()->addHours(24),
            ],
        );
    }
}
