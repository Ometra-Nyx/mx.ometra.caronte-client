<?php

/**
 * HTTP client for Caronte role management API.
 *
 * Provides methods for managing roles via the Caronte API using app-level
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

use Ometra\Caronte\CaronteRoleManager;

/**
 * Handles HTTP requests for role management to the Caronte authentication server.
 *
 * @author Gabriel Ruelas
 * @license MIT
 * @version 1.4.0
 */
class RoleApi extends BaseApiClient
{
    private function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns all roles for the current application.
     *
     * @return array{success: bool, data: string|null, error: string|null}
     */
    public static function showRoles(): array
    {
        return self::makeRequest(
            method: 'get',
            endpoint: 'api/app/applications/' . CaronteRoleManager::getAppId() . '/roles'
        );
    }

    /**
     * Creates a new role for the current application.
     *
     * @param  string $name        Role name.
     * @param  string $description Role description.
     * @return array{success: bool, data: string|null, error: string|null}
     */
    public static function createRole(string $name, string $description): array
    {
        return self::makeRequest(
            method: 'post',
            endpoint: 'api/app/applications/' . CaronteRoleManager::getAppId() . '/roles',
            data: [
                'description' => $description,
                'name'        => $name,
            ]
        );
    }

    /**
     * Updates an existing role's description.
     *
     * @param  string $uriApplicationRole Application role URI identifier.
     * @param  string $description        Updated role description.
     * @return array{success: bool, data: string|null, error: string|null}
     */
    public static function updateRole(string $uriApplicationRole, string $description): array
    {
        return self::makeRequest(
            method: 'put',
            endpoint: 'api/app/applications/' . CaronteRoleManager::getAppId() . '/roles/' . $uriApplicationRole,
            data: ['description' => $description]
        );
    }

    /**
     * Deletes a role from the current application.
     *
     * @param  string $uriApplicationRole Application role URI identifier.
     * @return array{success: bool, data: string|null, error: string|null}
     */
    public static function deleteRole(string $uriApplicationRole): array
    {
        return self::makeRequest(
            method: 'delete',
            endpoint: 'api/app/applications/' . CaronteRoleManager::getAppId() . '/roles/' . $uriApplicationRole
        );
    }
}
