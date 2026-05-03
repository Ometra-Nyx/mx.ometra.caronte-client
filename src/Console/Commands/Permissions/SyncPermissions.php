<?php

namespace Ometra\Caronte\Console\Commands\Permissions;

use Illuminate\Console\Command;
use Ometra\Caronte\Api\PermissionApi;
use Ometra\Caronte\Support\ConfiguredPermissions;

class SyncPermissions extends Command
{
    protected $signature = 'caronte:permissions:sync {--dry-run : Show the normalized configured permissions without pushing them to Caronte}';

    protected $description = 'Synchronize application API permissions defined in config/caronte.php with the Caronte server.';

    public function handle(): int
    {
        $permissions = ConfiguredPermissions::all();

        if ($this->option('dry-run')) {
            $this->table(['permission', 'description'], $permissions);

            return self::SUCCESS;
        }

        $response = PermissionApi::syncPermissions($permissions);
        $this->info((string) ($response['message'] ?? 'Permissions synchronized'));

        return self::SUCCESS;
    }
}
