<?php

namespace Ometra\Caronte\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ometra\Caronte\Facades\Caronte;
use Ometra\Caronte\Helpers\PermissionHelper;
use Ometra\Caronte\Support\CaronteResponse;
use Ometra\Caronte\Support\RequestContext;
use Symfony\Component\HttpFoundation\Response;

class ValidateSession
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = Caronte::getToken();

            if (!PermissionHelper::hasApplication()) {
                return CaronteResponse::forbidden(
                    message: 'User does not have access to this application.',
                    errors: ['User does not have access to this application.'],
                    forwardUrl: (string) config('caronte.LOGIN_URL')
                );
            }

            $response = $next($request);

            if (Caronte::tokenWasExchanged() && RequestContext::isApi()) {
                $response->headers->set('X-User-Token', $token->toString());
            }

            return $response;
        } catch (\Throwable $exception) {
            return CaronteResponse::unauthorized(
                message: $exception->getMessage(),
                forwardUrl: (string) config('caronte.LOGIN_URL')
            );
        }
    }
}
