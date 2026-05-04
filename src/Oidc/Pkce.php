<?php

namespace Ometra\Caronte\Oidc;

final class Pkce
{
    public static function verifier(): string
    {
        return Base64Url::encode(random_bytes(48));
    }

    public static function challenge(string $verifier): string
    {
        return Base64Url::encode(hash('sha256', $verifier, true));
    }
}
