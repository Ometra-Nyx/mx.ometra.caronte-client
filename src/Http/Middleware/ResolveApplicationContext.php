<?php

namespace Ometra\Caronte\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ometra\Caronte\Facades\Caronte;
use Ometra\Caronte\Support\CaronteApplicationToken;
use Ometra\Caronte\Support\CaronteApplicationContext;
use Ometra\Caronte\Support\CaronteResponse;
use Ometra\Caronte\Support\CaronteTenancy;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ResolveApplicationContext
{
    private const TENANT_REQUIRED = 'tenant_required';

    public function handle(Request $request, Closure $next, string $tenantMode = ''): Response
    {
        $token = trim((string) $request->header('X-Application-Token'));

        if ($token === '') {
            return CaronteResponse::unauthorized(
                message: 'No application token provided.',
                errors: ['X-Application-Token header is required.']
            );
        }

        $matchType = CaronteApplicationToken::matchType($token);

        if ($matchType === null) {
            return CaronteResponse::unauthorized(
                message: 'Invalid application token.',
                errors: ['The provided X-Application-Token does not match the configured application.']
            );
        }

        app()->instance(CaronteApplicationContext::class, new CaronteApplicationContext(
            appCn: CaronteApplicationToken::cn(),
            appId: CaronteApplicationToken::appId(),
            applicationToken: $token,
            authenticatedAsGroup: $matchType === 'application_group',
            groupId: $matchType === 'application_group' ? CaronteApplicationToken::groupId() : null,
        ));

        $tenantResponse = static::resolveTenant(
            request: $request,
            required: $tenantMode === self::TENANT_REQUIRED
        );

        if ($tenantResponse instanceof Response) {
            return $tenantResponse;
        }

        return $next($request);
    }

    public static function resolveTenant(Request $request, bool $required): ?Response
    {
        $tenantId = trim((string) $request->header('X-Tenant-Id'));
        $authenticatedTenantId = static::authenticatedTenantId();

        if (CaronteTenancy::isSingleTenant()) {
            $configuredTenantId = CaronteTenancy::requireConfiguredTenantId();

            if ($tenantId !== '' && $tenantId !== $configuredTenantId) {
                return CaronteResponse::forbidden(
                    message: 'Tenant mismatch.',
                    errors: ['Tenant mismatch.']
                );
            }

            if ($authenticatedTenantId !== null && $authenticatedTenantId !== $configuredTenantId) {
                return CaronteResponse::forbidden(
                    message: 'Tenant mismatch.',
                    errors: ['Tenant mismatch.']
                );
            }

            CaronteTenancy::bindTenantContext($configuredTenantId);

            return null;
        }

        if ($authenticatedTenantId !== null) {
            if ($tenantId !== '' && $tenantId !== $authenticatedTenantId) {
                return CaronteResponse::forbidden(
                    message: 'Tenant override is not allowed.',
                    errors: ['X-Tenant-Id must match the authenticated user tenant.']
                );
            }

            $tenantId = $authenticatedTenantId;
        }

        if ($tenantId === '') {
            return $required
                ? CaronteResponse::badRequest(
                    message: 'tenant_id is required',
                    errors: ['X-Tenant-Id header is required.']
                )
                : null;
        }

        CaronteTenancy::bindTenantContext($tenantId);

        return null;
    }

    private static function authenticatedTenantId(): ?string
    {
        try {
            $tenantId = trim((string) Caronte::getTenantId());
        } catch (Throwable) {
            return null;
        }

        return $tenantId !== '' ? $tenantId : null;
    }
}
