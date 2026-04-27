<?php

namespace Ometra\Caronte\Contracts;

interface SendsTwoFactorChallenge
{
    public function send(string $email, string $actionUrl, ?string $expiresAt = null): void;
}
