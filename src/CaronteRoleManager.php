<?php

namespace Ometra\Caronte;

use Ometra\Caronte\Api\RoleApi;
use Ometra\Caronte\Support\ApplicationToken;
use Ometra\Caronte\Support\ConfiguredRoles;

class CaronteRoleManager
{
    public static function getToken(): string
    {
        return ApplicationToken::make();
    }

    public static function getAppId(): string
    {
        return ApplicationToken::appId();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getRemoteRoles(): array
    {
        $response = RoleApi::showRoles();
        $roles = is_array($response['data']) ? $response['data'] : [];
        $mapped = [];

        foreach ($roles as $role) {
            if (!is_array($role) || !isset($role['name'])) {
                continue;
            }

            $mapped[(string) $role['name']] = $role;
        }

        return $mapped;
    }

    /**
     * @return array<int, array{name: string, description: string, uri_applicationRole: string}>
     */
    public static function getConfiguredRoles(): array
    {
        return ConfiguredRoles::all();
    }

    /**
     * @return array{configured: array<int, array{name: string, description: string, uri_applicationRole: string}>, remote: array<string, array<string, mixed>>, missing: array<int, string>, outdated: array<int, string>}
     */
    public static function previewSync(): array
    {
        $configured = static::getConfiguredRoles();
        $remote = static::getRemoteRoles();
        $missing = [];
        $outdated = [];

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

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function syncConfiguredRoles(): array
    {
        $roles = array_map(
            fn(array $role): array => [
                'name' => $role['name'],
                'description' => $role['description'],
            ],
            static::getConfiguredRoles()
        );

        return RoleApi::syncRoles($roles);
    }
}
