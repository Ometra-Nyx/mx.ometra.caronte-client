<?php

namespace Ometra\Caronte\Support;

use Equidna\Toolkit\Helpers\RouteHelper;
use Illuminate\Http\Request;

// NOTE FIND ANOTHER WAT TO DETECT THIS WITHOUT TIGHT COUPLING TO LARAVEL, OR AT LEAST DECOUPLE IT FROM THE REQUEST CLASS

class RouteMode
{
    public static function wantsJson(): bool
    {
        if (RouteHelper::wantsJson()) {
            return true;
        }

        $request = static::request();

        return $request !== null
            && (
                $request->expectsJson()
                || $request->wantsJson()
                || $request->is('api/*')
            );
    }

    public static function isWeb(): bool
    {
        return static::request() !== null && !static::wantsJson();
    }

    private static function request(): ?Request
    {
        if (!app()->bound('request')) {
            return null;
        }

        $request = request();

        return $request instanceof Request ? $request : null;
    }
}
