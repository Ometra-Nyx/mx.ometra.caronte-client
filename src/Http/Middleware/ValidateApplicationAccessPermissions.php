<?php

namespace Ometra\Caronte\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ometra\Caronte\Support\CaronteApplicationAccessContext;
use Ometra\Caronte\Support\CaronteResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ValidateApplicationAccessPermissions
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        try {
            /** @var CaronteApplicationAccessContext $context */
            $context = app(CaronteApplicationAccessContext::class);

            $required = collect($permissions)
                ->map(fn(mixed $permission): string => strtolower(trim((string) $permission)))
                ->filter()
                ->values();

            if ($required->isNotEmpty() && $required->contains(fn(string $permission): bool => ! $context->hasPermission($permission))) {
                return CaronteResponse::forbidden(
                    message: 'Application token does not have access to this feature.',
                    errors: ['Application token does not have the required permissions: ' . $required->implode(', ')]
                );
            }

            return $next($request);
        } catch (Throwable $exception) {
            return CaronteResponse::unauthorized(
                message: $exception->getMessage(),
                errors: [$exception->getMessage()]
            );
        }
    }
}
