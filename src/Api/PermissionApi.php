<?php

namespace Ometra\Caronte\Api;

class PermissionApi
{
    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function showPermissions(): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'get',
            endpoint: 'api/applications/permissions'
        );
    }

    /**
     * @param  array<int, array{permission: string, description: string}>  $permissions
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function syncPermissions(array $permissions): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'put',
            endpoint: 'api/applications/permissions',
            payload: [
                'permissions' => array_values($permissions),
            ]
        );
    }
}
