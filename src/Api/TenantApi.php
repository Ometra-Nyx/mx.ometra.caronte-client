<?php

namespace Ometra\Caronte\Api;

class TenantApi
{
    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function listTenants(string $search = ''): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'get',
            endpoint: 'api/tenants',
            query: ['search' => $search]
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function showTenant(string $tenantId): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'get',
            endpoint: 'api/tenants/' . $tenantId
        );
    }
}
