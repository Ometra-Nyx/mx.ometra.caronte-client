<?php

namespace Ometra\Caronte\Tenancy\Resolvers;

use Equidna\BeeHive\Contracts\TenantResolverInterface;
use Equidna\BeeHive\Exceptions\BeeHiveException;
use Ometra\Caronte\Facades\Caronte;

class CaronteTenantResolver implements TenantResolverInterface
{
    public function resolveTenantId(): string|null
    {
        if (!class_exists(\Ometra\Caronte\Facades\Caronte::class)) {
            throw new BeeHiveException('Caronte is not installed');
        }

        $tenant = Caronte::getTenantId();

        return $tenant;
    }
}
