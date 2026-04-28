<?php

namespace Ometra\Caronte\Api;

class RoleApi
{
    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function showRoles(): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'get',
            endpoint: 'api/applications/roles'
        );
    }

    /**
     * @param  array<int, array{name: string, description: string}>  $roles
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function syncRoles(array $roles): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'put',
            endpoint: 'api/applications/roles',
            payload: [
                'roles' => array_values($roles),
            ]
        );
    }
}
