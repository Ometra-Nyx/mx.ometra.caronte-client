<?php

namespace Ometra\Caronte\Support;

use Equidna\BeeHive\Tenancy\TenantContext;
use Ometra\Caronte\Facades\Caronte;
use RuntimeException;

class TenantContextResolver
{
    public static function resolve(?string $tenantId = null, bool $required = true): ?string
    {
        $tenantId = is_string($tenantId) ? trim($tenantId) : '';

        if ($tenantId !== '') {
            return $tenantId;
        }

        /** @var TenantContext $context */
        $context = app(TenantContext::class);
        $fromContext = $context->get();

        if (is_string($fromContext) && trim($fromContext) !== '') {
            return trim($fromContext);
        }

        try {
            $tenantId = trim((string) Caronte::getTenantId());
        } catch (\Throwable) {
            $tenantId = '';
        }

        if ($tenantId !== '') {
            return $tenantId;
        }

        if ($required) {
            throw new RuntimeException('Tenant context is required. Provide --tenant or authenticate with a tenant-scoped user.');
        }

        return null;
    }
}
