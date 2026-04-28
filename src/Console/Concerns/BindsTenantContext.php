<?php

namespace Ometra\Caronte\Console\Concerns;

use Equidna\BeeHive\Tenancy\TenantContext;
use RuntimeException;

trait BindsTenantContext
{
    protected function bindTenantContextFromOption(): string
    {
        $tenant = trim((string) $this->option('tenant'));

        if ($tenant === '') {
            $tenant = trim((string) $this->ask('Tenant identifier'));
        }

        if ($tenant === '') {
            throw new RuntimeException('The --tenant option is required for user management commands.');
        }

        $tenantContext = new TenantContext();
        $tenantContext->set($tenant);
        app()->instance(TenantContext::class, $tenantContext);

        return $tenant;
    }
}
