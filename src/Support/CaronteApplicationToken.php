<?php

namespace Ometra\Caronte\Support;

class CaronteApplicationToken
{
    public static function cn(): string
    {
        return strtolower(trim((string) config('caronte.app_cn')));
    }

    public static function appId(): string
    {
        return sha1(static::cn());
    }

    public static function groupId(): string
    {
        return strtolower(trim((string) config('caronte.application_group_id', '')));
    }

    public static function hasGroup(): bool
    {
        return static::groupId() !== ''
            && trim((string) config('caronte.application_group_secret', '')) !== '';
    }

    public static function make(): string
    {
        return base64_encode(static::appId() . ':' . (string) config('caronte.app_secret'));
    }

    public static function makeGroup(): string
    {
        if (!static::hasGroup()) {
            return '';
        }

        return base64_encode(static::groupId() . ':' . (string) config('caronte.application_group_secret'));
    }

    public static function matches(?string $token): bool
    {
        return static::matchType($token) !== null;
    }

    public static function matchType(?string $token): ?string
    {
        if (!is_string($token) || $token === '') {
            return null;
        }

        $token = trim($token);

        if (hash_equals(static::make(), $token)) {
            return 'application';
        }

        if (static::hasGroup() && hash_equals(static::makeGroup(), $token)) {
            return 'application_group';
        }

        return null;
    }
}
