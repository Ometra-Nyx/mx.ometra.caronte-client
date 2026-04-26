<?php

namespace Ometra\Caronte\Api;

use Ometra\Caronte\Support\TenantContextResolver;

class ClientApi extends BaseApiClient
{
    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function showUsers(string $search = '', bool $usersApp = true, ?string $tenantId = null): array
    {
        return static::http()->applicationRequest(
            method: 'get',
            endpoint: 'api/users',
            query: [
                'search' => $search,
                'app_users' => $usersApp ? 'true' : 'false',
            ],
            tenantId: TenantContextResolver::resolve($tenantId)
        );
    }

    /**
     * @param  array{name: string, email: string, password: string, password_confirmation: string}  $attributes
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function createUser(array $attributes, ?string $tenantId = null): array
    {
        return static::http()->applicationRequest(
            method: 'post',
            endpoint: 'api/users',
            payload: $attributes,
            tenantId: TenantContextResolver::resolve($tenantId)
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function showUser(string $uriUser, ?string $tenantId = null): array
    {
        return static::http()->applicationRequest(
            method: 'get',
            endpoint: 'api/users/' . $uriUser,
            tenantId: TenantContextResolver::resolve($tenantId)
        );
    }

    /**
     * @param  array{name: string}  $attributes
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function updateUser(string $uriUser, array $attributes, ?string $tenantId = null): array
    {
        return static::http()->applicationRequest(
            method: 'put',
            endpoint: 'api/users/' . $uriUser,
            payload: $attributes,
            tenantId: TenantContextResolver::resolve($tenantId)
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function deleteUser(string $uriUser, ?string $tenantId = null): array
    {
        return static::http()->applicationRequest(
            method: 'delete',
            endpoint: 'api/users/' . $uriUser,
            tenantId: TenantContextResolver::resolve($tenantId)
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function showUserRoles(string $uriUser, ?string $tenantId = null): array
    {
        return static::http()->applicationRequest(
            method: 'get',
            endpoint: 'api/users/' . $uriUser . '/roles',
            tenantId: TenantContextResolver::resolve($tenantId)
        );
    }

    /**
     * @param  array<int, string>  $roleUris
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function syncUserRoles(string $uriUser, array $roleUris, ?string $tenantId = null): array
    {
        return static::http()->applicationRequest(
            method: 'put',
            endpoint: 'api/users/' . $uriUser . '/roles',
            payload: [
                'roles' => array_values(array_unique($roleUris)),
            ],
            tenantId: TenantContextResolver::resolve($tenantId)
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function storeUserMetadata(string $uriUser, array $metadata, ?string $tenantId = null): array
    {
        return static::http()->applicationRequest(
            method: 'post',
            endpoint: 'api/users/' . $uriUser . '/metadata',
            payload: $metadata,
            tenantId: TenantContextResolver::resolve($tenantId)
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function deleteUserMetadata(string $uriUser, string $key, ?string $tenantId = null): array
    {
        return static::http()->applicationRequest(
            method: 'delete',
            endpoint: 'api/users/' . $uriUser . '/metadata',
            payload: ['key' => $key],
            tenantId: TenantContextResolver::resolve($tenantId)
        );
    }
}
