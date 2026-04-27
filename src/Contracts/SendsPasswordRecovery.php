<?php

namespace Ometra\Caronte\Contracts;

interface SendsPasswordRecovery
{
    public function send(string $email, string $actionUrl, ?string $expiresAt = null): void;
}
