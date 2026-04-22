<?php

/**
 * Console command to update role descriptions interactively.
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

class UpdateRole extends Command
{
    protected $signature   = 'caronte-client:update-role {uri_rol}';
    protected $description = 'Update a role within the application';

    public function handle(): int
    {
        $uri_applicationRole = $this->argument('uri_rol');
        $description = $this->ask('Escribe la nueva descripción del rol:');
        $response = CaronteRoleManager::updateRole($uri_applicationRole, $description);
        $this->info("¡Listo! El rol '{$uri_applicationRole}' ha sido actualizado exitosamente.");

        return 0;
    }
}
