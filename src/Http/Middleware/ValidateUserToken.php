<?php

namespace Ometra\Caronte\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ometra\Caronte\Facades\Caronte;
use Ometra\Caronte\Helpers\PermissionHelper;
use Ometra\Caronte\Support\CaronteResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ValidateUserToken
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = Caronte::getToken();

            if (!PermissionHelper::hasApplication()) {
                return CaronteResponse::forbidden(
                    message: 'User does not have access to this application.',
                    errors: ['User does not have access to this application.'],
                    forwardUrl: (string) config('caronte.login_url')
                );
            }

            $response = $next($request);

            if (
                Caronte::tokenWasExchanged()
                && ($request->expectsJson() || $request->wantsJson() || $request->is('api/*'))
            ) {
                $response->headers->set('X-User-Token', $token->toString());
            }

            return $response;
        } catch (Throwable $exception) {
            return CaronteResponse::unauthorized(
                message: $exception->getMessage(),
                forwardUrl: (string) config('caronte.login_url')
            );
        }
    }
}
