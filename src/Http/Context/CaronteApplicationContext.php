<?php

namespace Ometra\Caronte\Http\Context;

class CaronteApplicationContext
{
    public function __construct(
        public readonly string $appCn,
        public readonly string $appId,
        public readonly string $applicationToken,
        public readonly ?string $tenantId = null,
    ) {
        //
    }

    public function withTenant(?string $tenantId): self
    {
        return new self(
            appCn: $this->appCn,
            appId: $this->appId,
            applicationToken: $this->applicationToken,
            tenantId: $tenantId,
        );
    }
}
