<?php

namespace Ometra\Caronte\Console\Commands\Users;

use Illuminate\Console\Command;
use Ometra\Caronte\Api\ClientApi;
use Ometra\Caronte\Console\Concerns\BindsTenantContext;

class UpdateUser extends Command
{
    use BindsTenantContext;

    protected $signature = 'caronte:users:update
        {uri_user? : Caronte user URI}
        {--tenant= : Tenant identifier required for user-scoped Caronte endpoints}
        {--name= : Updated display name}';

    protected $description = 'Update a user name in Caronte.';

    public function handle(): int
    {
        $uriUser = trim((string) ($this->argument('uri_user') ?: $this->ask('User URI')));
        $name = trim((string) ($this->option('name') ?: $this->ask('New display name')));

        if ($uriUser === '' || $name === '') {
            $this->error('User URI and name are required.');

            return self::FAILURE;
        }

        try {
            $this->bindTenantContextFromOption();

            $response = ClientApi::updateUser($uriUser, ['name' => $name]);
            $this->info($response['message']);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
