<?php

namespace Ometra\Caronte\Console\Concerns;

trait GuardsManagement
{
    protected function ensureManagementEnabled(): bool
    {
        if (config('caronte.management.enabled', true)) {
            return true;
        }

        $this->error('Caronte user management is disabled by config(caronte.management.enabled).');

        return false;
    }
}
