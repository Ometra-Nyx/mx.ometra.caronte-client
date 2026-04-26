<?php

namespace Ometra\Caronte\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ometra\Caronte\Http\Context\CaronteApplicationContext;
use Ometra\Caronte\Support\ApplicationToken;
use Ometra\Caronte\Support\CaronteResponse;
use Symfony\Component\HttpFoundation\Response;

class ResolveApplicationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = trim((string) $request->header('X-Application-Token'));

        if ($token === '') {
            return CaronteResponse::unauthorized(
                message: 'No application token provided.',
                errors: ['X-Application-Token header is required.']
            );
        }

        if (!ApplicationToken::matches($token)) {
            return CaronteResponse::unauthorized(
                message: 'Invalid application token.',
                errors: ['The provided X-Application-Token does not match the configured application.']
            );
        }

        app()->instance(CaronteApplicationContext::class, new CaronteApplicationContext(
            appCn: ApplicationToken::cn(),
            appId: ApplicationToken::appId(),
            applicationToken: $token,
        ));

        return $next($request);
    }
}
