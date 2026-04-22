<?php

/**
 * HTTP client for Caronte user and client management API.
 *
 * Provides methods for managing users via the Caronte API using app-level
 * authentication tokens. All methods return standardized response arrays.
 *
 * PHP 8.1+
 *
 * @package   Ometra\Caronte\Api
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/Ometra-Core/mx.ometra.caronte-client Documentation
 */

namespace Ometra\Caronte\Api;

use Equidna\BeeHive\Tenancy\TenantContext;

/**
 * Handles HTTP requests for user management to the Caronte authentication server.
 *
 * @author Gabriel Ruelas
 * @license MIT
 * @version 1.4.0
 */
class ClientApi extends BaseApiClient
{
    private function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns users matching the search criteria.
     *
     * @param  string $paramSearch  Search term for filtering users.
     * @param  bool   $usersApp     Filter by application users only.
     * @return array{success: bool, data: string|null, error: string|null}
     */
    public static function showUsers(string $paramSearch, bool $usersApp = false): array
    {
        return self::makeRequest(
            method: 'get',
            endpoint: 'api/app/users/',
            data: self::withTenantPayload([
                'search' => $paramSearch,
                'app_users' => $usersApp ? 'true' : 'false',
            ])
        );
    }

    /**
     * Creates a new user in the Caronte system.
     *
     * @param  string $name                  User's full name.
     * @param  string $email                 User's email address.
     * @param  string $password              User's password.
     * @param  string $password_confirmation Password confirmation.
     * @return array{success: bool, data: string|null, error: string|null}
     */
    public static function createUser(string $name, string $email, string $password, string $password_confirmation): array
    {
        return self::makeRequest(
            method: 'post',
            endpoint: 'api/app/users',
            data: self::withTenantPayload([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password_confirmation,
            ])
        );
    }

    /**
     * Updates a user's name.
     *
     * @param  string $uri_user User URI identifier.
     * @param  string $name     Updated user name.
     * @return array{success: bool, data: string|null, error: string|null}
     */
    public static function updateUser(string $uri_user, string $name): array
    {
        return self::makeRequest(
            method: 'put',
            endpoint: 'api/app/users/' . $uri_user,
            data: self::withTenantPayload(['name' => $name])
        );
    }

    /**
     * Deletes a user from the Caronte system.
     *
     * @param  string $uri_user User URI identifier.
     * @return array{success: bool, data: string|null, error: string|null}
     */
    public static function deleteUser(string $uri_user): array
    {
        return self::makeRequest(
            method: 'delete',
            endpoint: 'api/app/users/' . $uri_user,
            data: self::withTenantPayload()
        );
    }

    /**
     * Returns all roles assigned to a user.
     *
     * @param  string $uri_user User URI identifier.
     * @return array{success: bool, data: string|null, error: string|null}
     */
    public static function showUserRoles(string $uri_user): array
    {
        return self::makeRequest(
            method: 'get',
            endpoint: 'api/app/users/' . $uri_user . '/roles',
            data: self::withTenantPayload()
        );
    }

    /**
     * Assigns a role to a user.
     *
     * @param  string $uriUser            User URI identifier.
     * @param  string $uriApplicationRole Application role URI identifier.
     * @return array{success: bool, data: string|null, error: string|null}
     */
    public static function assignRoleToUser(string $uriUser, string $uriApplicationRole): array
    {
        return self::makeRequest(
            method: 'post',
            endpoint: 'api/app/users/roles/' . $uriApplicationRole . '/' . $uriUser,
            data: self::withTenantPayload([
                'uri_user' => $uriUser,
                'uri_applicationRole' => $uriApplicationRole,
            ])
        );
    }

    /**
     * Removes a role from a user.
     *
     * @param  string $uri_user           User URI identifier.
     * @param  string $uri_applicationRole Application role URI identifier.
     * @return array{success: bool, data: string|null, error: string|null}
     */
    public static function deleteUserRole(string $uri_user, string $uri_applicationRole): array
    {
        return self::makeRequest(
            method: 'delete',
            endpoint: 'api/app/users/roles/' . $uri_applicationRole . '/' . $uri_user,
            data: self::withTenantPayload()
        );
    }

    /**
     * Adds tenant identifier to the request payload.
     *
     * @param  array<string, mixed> $data Base request payload.
     * @return array<string, mixed>
     */
    private static function withTenantPayload(array $data = []): array
    {
        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);

        $data['id_tenant'] = $tenantContext->get();

        return $data;
    }
}
