<?php

namespace Ometra\Caronte\Http\Middleware;

use Closure;
use Equidna\BeeHive\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Ometra\Caronte\Http\Context\CaronteApplicationContext;
use Ometra\Caronte\Support\CaronteResponse;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->bound(CaronteApplicationContext::class)) {
            return CaronteResponse::badRequest(
                message: 'Application context not resolved.',
                errors: ['Apply the caronte.application middleware before caronte.tenant.']
            );
        }

        $tenantId = trim((string) $request->header('X-Tenant-Id'));

        if ($tenantId === '') {
            return CaronteResponse::badRequest(
                message: 'tenant_id is required',
                errors: ['X-Tenant-Id header is required.']
            );
        }

        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);
        $tenantContext->set($tenantId);
        $request->attributes->set('tenant_id', $tenantId);

        /** @var CaronteApplicationContext $context */
        $context = app(CaronteApplicationContext::class);
        app()->instance(CaronteApplicationContext::class, $context->withTenant($tenantId));

        return $next($request);
    }
}
