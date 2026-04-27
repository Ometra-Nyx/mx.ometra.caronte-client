<?php

namespace Ometra\Caronte\Support;

use Illuminate\Http\Request;

class RequestContext
{
    public static function hasRequest(): bool
    {
        return app()->bound('request');
    }

    public static function request(): ?Request
    {
        if (!static::hasRequest()) {
            return null;
        }

        /** @var Request $request */
        $request = request();

        return $request;
    }

    public static function isApi(): bool
    {
        $request = static::request();

        if ($request === null) {
            return false;
        }

        return $request->expectsJson()
            || $request->wantsJson()
            || $request->is('api/*');
    }

    public static function isWeb(): bool
    {
        return static::request() !== null && !static::isApi();
    }
}
