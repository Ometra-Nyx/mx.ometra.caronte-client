<?php

/**
 * Console command to display roles assigned to a specific user.
 *
 * PHP 8.1+
 *
 * @package   Ometra\Caronte\Console\Commands\Users
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/Ometra-Core/mx.ometra.caronte-client Documentation
 */

namespace Ometra\Caronte\Console\Commands\Users;

use Illuminate\Console\Command;
use Ometra\Caronte\Api\ClientApi;

/**
 * Displays all roles attached to a user within the application.
 *
 * @author Gabriel Ruelas
 * @license MIT
 * @version 1.4.0
 */
class ShowRolesByUser extends Command
{
    protected $signature   = 'caronte-client:show-user-roles {uri_user}';
    protected $description = 'Show Roles attached by user within the application';

    /**
     * Executes the command to display user roles.
     *
     * @return int Exit code (0 on success).
     */
    public function handle(): int
    {
        $uri_user = $this->argument('uri_user');
        $response = ClientApi::showUserRoles(uri_user: $uri_user);
        $roles = json_decode($response['data'], true);

        if (empty($roles)) {
            $this->warn("No hay roles registrados.");
            return 0;
        }

        $rows = collect($roles)->map(function ($role) {
            return [
                $role['name'],
                $role['description'],
            ];
        })->all();

        $this->table(
            headers: ['Nombre', 'Descripción'],
            rows: $rows
        );

        return 0;
    }
}
