<?php

namespace Ometra\Caronte\Console\Commands\Roles;

use Illuminate\Console\Command;
use Ometra\Caronte\CaronteRoleManager;

class SyncRoles extends Command
{
    protected $signature = 'caronte:roles:sync {--dry-run : Show the normalized configured roles without pushing them to Caronte}';

    protected $description = 'Synchronize roles defined in config/caronte.php with the Caronte server.';

    public function handle(): int
    {
        $preview = CaronteRoleManager::previewSync();

        $this->table(
            ['Role', 'Description', 'URI', 'Remote status'],
            array_map(function (array $role) use ($preview): array {
                $remote = $preview['remote'][$role['name']] ?? null;
                $status = $remote === null
                    ? 'missing'
                    : (($remote['description'] ?? null) === $role['description'] ? 'ok' : 'outdated');

                return [
                    $role['name'],
                    $role['description'],
                    $role['uri_applicationRole'],
                    $status,
                ];
            }, $preview['configured'])
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run completed. No remote changes were sent.');

            return self::SUCCESS;
        }

        $response = CaronteRoleManager::syncConfiguredRoles();
        $this->info($response['message']);

        return self::SUCCESS;
    }
}
