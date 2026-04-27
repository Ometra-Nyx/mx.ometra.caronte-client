<?php

namespace Ometra\Caronte\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorChallengeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $actionUrl,
        public readonly ?string $expiresAt = null,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your two-factor login link',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'caronte::emails.auth.two-factor-challenge',
        );
    }
}
