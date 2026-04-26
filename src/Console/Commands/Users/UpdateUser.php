<?php

namespace Ometra\Caronte\Console\Commands\Users;

use Illuminate\Console\Command;
use Ometra\Caronte\Api\ClientApi;
use Ometra\Caronte\Console\Concerns\GuardsManagement;

class UpdateUser extends Command
{
    use GuardsManagement;

    protected $signature = 'caronte:users:update
        {uri_user? : Caronte user URI}
        {--tenant= : Tenant identifier required for user-scoped Caronte endpoints}
        {--name= : Updated display name}';

    protected $description = 'Update a user name in Caronte.';

    public function handle(): int
    {
        if (!$this->ensureManagementEnabled()) {
            return self::FAILURE;
        }

        $uriUser = trim((string) ($this->argument('uri_user') ?: $this->ask('User URI')));
        $name = trim((string) ($this->option('name') ?: $this->ask('New display name')));

        if ($uriUser === '' || $name === '') {
            $this->error('User URI and name are required.');

            return self::FAILURE;
        }

        try {
            $response = ClientApi::updateUser($uriUser, ['name' => $name], $this->resolveTenant());
            $this->info($response['message']);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveTenant(): string
    {
        $tenant = trim((string) $this->option('tenant'));

        if ($tenant === '') {
            $tenant = trim((string) $this->ask('Tenant identifier'));
        }

        if ($tenant === '') {
            throw new \RuntimeException('The --tenant option is required for user management commands.');
        }

        return $tenant;
    }
}
