<?php

namespace Ometra\Caronte\Console\Commands\Users;

use Illuminate\Console\Command;
use Ometra\Caronte\Api\ClientApi;
use Ometra\Caronte\Console\Concerns\BindsTenantContext;
use Ometra\Caronte\Support\ConfiguredRoles;

class SyncUserRoles extends Command
{
    use BindsTenantContext;


    protected $signature = 'caronte:users:roles:sync
        {uri_user? : Caronte user URI}
        {--tenant= : Tenant identifier required for user-scoped Caronte endpoints}
        {--role=* : Configured role names to assign}
        {--clear : Remove every assigned role for the user within this application}';

    protected $description = 'Synchronize the configured role set for a user.';

    public function handle(): int
    {
        $uriUser = trim((string) ($this->argument('uri_user') ?: $this->ask('User URI')));

        if ($uriUser === '') {
            $this->error('A user URI is required.');

            return self::FAILURE;
        }

        try {
            $this->bindTenantContextFromOption();

            $roles = $this->option('clear') ? [] : $this->resolveRoles();
            $response = ClientApi::syncUserRoles($uriUser, $roles);
            $this->info($response['message']);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolveRoles(): array
    {
        $configured = ConfiguredRoles::keyedByName();
        $selected = array_values(array_filter((array) $this->option('role')));

        if ($selected === []) {
            $choices = array_keys($configured);
            $selected = (array) $this->choice(
                'Select the desired role set',
                $choices,
                default: null,
                attempts: null,
                multiple: true
            );
        }

        $uris = [];

        foreach ($selected as $role) {
            if (!isset($configured[$role])) {
                throw new \RuntimeException("Unknown configured role [{$role}].");
            }

            $uris[] = $configured[$role]['uri_applicationRole'];
        }

        return $uris;
    }
}
