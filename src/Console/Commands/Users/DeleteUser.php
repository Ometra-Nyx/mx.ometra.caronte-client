<?php

namespace Ometra\Caronte\Console\Commands\Users;

use Illuminate\Console\Command;
use Ometra\Caronte\Api\ClientApi;
use Ometra\Caronte\Console\Concerns\BindsTenantContext;

class DeleteUser extends Command
{
    use BindsTenantContext;

    protected $signature = 'caronte:users:delete
        {uri_user? : Caronte user URI}
        {--tenant= : Tenant identifier required for user-scoped Caronte endpoints}
        {--force : Delete without confirmation}';

    protected $description = 'Delete a user from Caronte.';

    public function handle(): int
    {
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
            $this->bindTenantContextFromOption();

            $response = ClientApi::deleteUser($uriUser);
            $this->info($response['message']);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
