<?php

namespace Ometra\Caronte\Notifications;

use Illuminate\Support\Facades\Mail;
use Ometra\Caronte\Contracts\SendsPasswordRecovery;
use Ometra\Caronte\Mail\PasswordRecoveryMail;

class LaravelPasswordRecoverySender implements SendsPasswordRecovery
{
    public function send(string $email, string $actionUrl, ?string $expiresAt = null): void
    {
        Mail::to($email)->send(new PasswordRecoveryMail(
            actionUrl: $actionUrl,
            expiresAt: $expiresAt,
        ));
    }
}
