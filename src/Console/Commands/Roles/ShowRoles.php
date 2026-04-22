<?php

/**
 * Console command to display all roles in a formatted table.
 *
 * PHP 8.1+
 *
 * @package   Ometra\Caronte\Console\Commands\Roles
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/Ometra-Core/mx.ometra.caronte-client Documentation
 */

namespace Ometra\Caronte\Console\Commands\Roles;

use Illuminate\Console\Command;
use Ometra\Caronte\CaronteRoleManager;

class ShowRoles extends Command
{
    protected $signature   = 'caronte-client:show-roles';
    protected $description = 'Show Roles within the application';

    public function handle(): int
    {
        $roles = CaronteRoleManager::getRoles();
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
