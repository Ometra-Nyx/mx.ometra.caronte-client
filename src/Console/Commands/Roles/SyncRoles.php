<?php

namespace Ometra\Caronte\Console\Commands\Roles;

use Illuminate\Console\Command;
use Ometra\Caronte\Api\RoleApi;
use Ometra\Caronte\Support\ConfiguredRoles;

class SyncRoles extends Command
{
    protected $signature = 'caronte:roles:sync {--dry-run : Show the normalized configured roles without pushing them to Caronte}';

    protected $description = 'Synchronize roles defined in config/caronte.php with the Caronte server.';

    public function handle(): int
    {
        $preview = $this->previewRoleSync();

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

        $roles = array_map(
            fn(array $role): array => [
                'name' => $role['name'],
                'description' => $role['description'],
            ],
            ConfiguredRoles::all()
        );
        $response = RoleApi::syncRoles($roles);
        $this->info($response['message']);

        return self::SUCCESS;
    }

    /**
     * @return array{configured: array<int, array{name: string, description: string, uri_applicationRole: string}>, remote: array<string, array<string, mixed>>, missing: array<int, string>, outdated: array<int, string>}
     */
    private function previewRoleSync(): array
    {
        $configured = ConfiguredRoles::all();
        $response = RoleApi::showRoles();
        $remoteRoles = is_array($response['data']) ? $response['data'] : [];
        $remote = [];
        $missing = [];
        $outdated = [];

        foreach ($remoteRoles as $role) {
            if (!is_array($role) || !isset($role['name'])) {
                continue;
            }
            $remote[(string) $role['name']] = $role;
        }

        foreach ($configured as $role) {
            $remoteRole = $remote[$role['name']] ?? null;

            if ($remoteRole === null) {
                $missing[] = $role['name'];
                continue;
            }

            if (($remoteRole['description'] ?? null) !== $role['description']) {
                $outdated[] = $role['name'];
            }
        }

        return compact('configured', 'remote', 'missing', 'outdated');
    }
}
