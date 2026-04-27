<?php

namespace Ometra\Caronte\Support;

class ApplicationToken
{
    public static function cn(): string
    {
        return strtolower(trim((string) config('caronte.APP_ID')));
    }

    public static function appId(): string
    {
        return sha1(static::cn());
    }

    public static function make(): string
    {
        return base64_encode(static::appId() . ':' . (string) config('caronte.APP_SECRET'));
    }

    public static function matches(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        return hash_equals(static::make(), trim($token));
    }
}
