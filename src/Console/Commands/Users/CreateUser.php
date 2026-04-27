<?php

namespace Ometra\Caronte\Console\Commands\Users;

use Illuminate\Console\Command;
use Ometra\Caronte\Api\ClientApi;
use Ometra\Caronte\Console\Concerns\GuardsManagement;
use Ometra\Caronte\Support\ConfiguredRoles;

class CreateUser extends Command
{
    use GuardsManagement;

    protected $signature = 'caronte:users:create
        {--tenant= : Tenant identifier required for user-scoped Caronte endpoints}
        {--name= : User display name}
        {--email= : User email}
        {--password= : Initial password}
        {--role=* : Configured role names to assign after creation}';

    protected $description = 'Create a user and assign a configured role set.';

    public function handle(): int
    {
        if (!$this->ensureManagementEnabled()) {
            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('User name')));
        $email = trim((string) ($this->option('email') ?: $this->ask('User email')));
        $password = (string) ($this->option('password') ?: $this->secret('Initial password'));
        $passwordConfirmation = (string) $this->secret('Confirm password');

        if ($name === '' || $email === '' || $password === '') {
            $this->error('Name, email, and password are required.');

            return self::FAILURE;
        }

        if ($password !== $passwordConfirmation) {
            $this->error('Password confirmation does not match.');

            return self::FAILURE;
        }

        try {
            $tenant = $this->resolveTenant();
            $response = ClientApi::createUser([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ], $tenant);

            $user = $response['data']['user'] ?? null;
            $roles = $this->resolveRoleUris();

            if (is_array($user) && isset($user['uri_user'])) {
                ClientApi::syncUserRoles((string) $user['uri_user'], $roles, $tenant);
            }

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
    private function resolveRoleUris(): array
    {
        $configured = ConfiguredRoles::keyedByName();
        $selected = array_values(array_filter((array) $this->option('role')));

        if ($selected === []) {
            $choices = array_keys($configured);
            $selected = (array) $this->choice('Select roles', $choices, default: null, attempts: null, multiple: true);
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
