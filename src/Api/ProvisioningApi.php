<?php

namespace Ometra\Caronte\Api;

class ProvisioningApi
{
    /**
     * @param  array{
     *     external_id?: string|null,
     *     tenant: array{name: string, description?: string|null},
     *     admin: array{email: string, name: string, password: string}
     * }  $payload
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function provisionTenant(array $payload): array
    {
        return app(CaronteApiClient::class)->authRequest(
            method: 'post',
            endpoint: 'api/provisioning/tenants',
            payload: $payload
        );
    }
}
