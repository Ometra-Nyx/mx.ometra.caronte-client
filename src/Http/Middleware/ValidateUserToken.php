<?php

namespace Ometra\Caronte\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ometra\Caronte\Facades\Caronte;
use Ometra\Caronte\Helpers\PermissionHelper;
use Ometra\Caronte\Support\CaronteResponse;
use Ometra\Caronte\Support\CaronteTenancy;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ValidateUserToken
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = Caronte::getToken();

            if (!PermissionHelper::hasApplication()) {
                Caronte::clearToken();

                return CaronteResponse::forbidden(
                    message: 'User does not have access to this application.',
                    errors: ['User does not have access to this application.'],
                    forwardUrl: (string) config('caronte.login_url')
                );
            }

            if (CaronteTenancy::isSingleTenant()) {
                $configuredTenantId = CaronteTenancy::requireConfiguredTenantId();

                try {
                    $tokenTenantId = Caronte::getTenantId();
                } catch (Throwable) {
                    Caronte::clearToken();

                    return CaronteResponse::forbidden(
                        message: 'Tenant is required for this application.',
                        errors: ['Tenant is required for this application.'],
                        forwardUrl: (string) config('caronte.login_url')
                    );
                }

                if ($tokenTenantId !== $configuredTenantId) {
                    Caronte::clearToken();

                    return CaronteResponse::forbidden(
                        message: 'Tenant mismatch.',
                        errors: ['Tenant mismatch.'],
                        forwardUrl: (string) config('caronte.login_url')
                    );
                }

                CaronteTenancy::bindTenantContext($configuredTenantId);
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
            Caronte::clearToken();

            return CaronteResponse::unauthorized(
                message: $exception->getMessage(),
                forwardUrl: (string) config('caronte.login_url')
            );
        }
    }
}
