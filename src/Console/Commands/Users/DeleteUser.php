<?php

namespace Ometra\Caronte\Console\Commands\Users;

use Illuminate\Console\Command;
use Ometra\Caronte\Api\ClientApi;
use Ometra\Caronte\Console\Concerns\GuardsManagement;

class DeleteUser extends Command
{
    use GuardsManagement;

    protected $signature = 'caronte:users:delete
        {uri_user? : Caronte user URI}
        {--tenant= : Tenant identifier required for user-scoped Caronte endpoints}
        {--force : Delete without confirmation}';

    protected $description = 'Delete a user from Caronte.';

    public function handle(): int
    {
        if (!$this->ensureManagementEnabled()) {
            return self::FAILURE;
        }

        $uriUser = trim((string) ($this->argument('uri_user') ?: $this->ask('User URI')));

        if ($uriUser === '') {
            $this->error('A user URI is required.');

            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm("Delete user {$uriUser}?")) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        try {
            $response = ClientApi::deleteUser($uriUser, $this->resolveTenant());
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
