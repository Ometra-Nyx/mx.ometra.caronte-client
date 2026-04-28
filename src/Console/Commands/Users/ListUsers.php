<?php

namespace Ometra\Caronte\Console\Commands\Users;

use Illuminate\Console\Command;
use Ometra\Caronte\Api\ClientApi;
use Ometra\Caronte\Console\Concerns\BindsTenantContext;

class ListUsers extends Command
{
    use BindsTenantContext;

    protected $signature = 'caronte:users:list
        {--tenant= : Tenant identifier required for user-scoped Caronte endpoints}
        {--search= : Optional name or email filter}
        {--all : Include users not currently linked to the application}';

    protected $description = 'List users visible to the configured Caronte application.';

    public function handle(): int
    {
        try {
            $this->bindTenantContextFromOption();

            $response = ClientApi::showUsers(
                search: (string) $this->option('search'),
                usersApp: !$this->option('all')
            );

            $users = is_array($response['data']) ? $response['data'] : [];

            if ($users === []) {
                $this->warn('No users were returned by Caronte.');

                return self::SUCCESS;
            }

            $this->table(
                ['URI', 'Name', 'Email'],
                array_map(fn(array $user): array => [
                    $user['uri_user'] ?? '',
                    $user['name'] ?? '',
                    $user['email'] ?? '',
                ], $users)
            );

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
