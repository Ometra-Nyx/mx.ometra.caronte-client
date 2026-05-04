<?php

namespace Ometra\Caronte\Support;

class CaronteApplicationAccessContext
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public readonly string $tokenId,
        public readonly string $appId,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly array $permissions,
    ) {
        //
    }

    public function hasPermission(string $permission): bool
    {
        return in_array(strtolower(trim($permission)), $this->permissions, true);
    }
}
