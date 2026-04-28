<?php

namespace Ometra\Caronte\Api;

class ClientApi
{
    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function showUsers(string $search = '', bool $usersApp = true): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'get',
            endpoint: 'api/users',
            query: [
                'search' => $search,
                'app_users' => $usersApp ? 'true' : 'false',
            ]
        );
    }

    /**
     * @param  array{name: string, email: string, password: string, password_confirmation: string}  $attributes
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function createUser(array $attributes): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'post',
            endpoint: 'api/users',
            payload: $attributes
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function showUser(string $uriUser): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'get',
            endpoint: 'api/users/' . $uriUser
        );
    }

    /**
     * @param  array{name: string}  $attributes
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function updateUser(string $uriUser, array $attributes): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'put',
            endpoint: 'api/users/' . $uriUser,
            payload: $attributes
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function deleteUser(string $uriUser): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'delete',
            endpoint: 'api/users/' . $uriUser
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function showUserRoles(string $uriUser): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'get',
            endpoint: 'api/users/' . $uriUser . '/roles'
        );
    }

    /**
     * @param  array<int, string>  $roleUris
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function syncUserRoles(string $uriUser, array $roleUris): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'put',
            endpoint: 'api/users/' . $uriUser . '/roles',
            payload: [
                'roles' => array_values(array_unique($roleUris)),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function storeUserMetadata(string $uriUser, array $metadata): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'post',
            endpoint: 'api/users/' . $uriUser . '/metadata',
            payload: $metadata
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function deleteUserMetadata(string $uriUser, string $key): array
    {
        return app(CaronteApiClient::class)->applicationRequest(
            method: 'delete',
            endpoint: 'api/users/' . $uriUser . '/metadata',
            payload: ['key' => $key]
        );
    }
}
