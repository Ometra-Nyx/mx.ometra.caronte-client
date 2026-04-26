<?php

namespace Ometra\Caronte\Notifications;

use Illuminate\Support\Facades\Mail;
use Ometra\Caronte\Contracts\SendsTwoFactorChallenge;
use Ometra\Caronte\Mail\TwoFactorChallengeMail;

class LaravelTwoFactorChallengeSender implements SendsTwoFactorChallenge
{
    public function send(string $email, string $actionUrl, ?string $expiresAt = null): void
    {
        Mail::to($email)->send(new TwoFactorChallengeMail(
            actionUrl: $actionUrl,
            expiresAt: $expiresAt,
        ));
    }
}
