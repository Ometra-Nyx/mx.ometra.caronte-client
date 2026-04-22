<?php

/**
 * Console command to remove roles from a user in the Caronte system.
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
 * Removes application roles assigned to a user.
 *
 * Provides interactive options to remove all roles or select specific roles
 * to remove from a user within the application context.
 *
 * @author Gabriel Ruelas
 * @license MIT
 * @version 1.4.0
 */
class DeleteRolesUser extends Command
{
    protected $signature   = 'caronte-client:delete-user-roles {uri_user} {name_user}';
    protected $description = 'Removes app roles that belong to the user';

    /**
     * Executes the command to delete user roles.
     *
     * @return int Exit code (0 on success, 1 on error).
     */
    public function handle(): int
    {
        $uri_user = $this->argument('uri_user');
        $name = $this->argument('name_user');

        $mainOptions = [
            'Desautorizar todos los roles del usuario',
            'Seleccionar roles específicos a remover',
            'Salir',
        ];

        $selectedOption = $this->choice(
            'Selecciona una opción:',
            $mainOptions,
            2
        );

        $option = array_search($selectedOption, $mainOptions);

        $roles = ClientApi::showUserRoles(uri_user: $uri_user);
        $roles = json_decode($roles['data'], true);

        if (empty($roles)) {
            $this->error("No se encontraron roles asociados al usuario");
            return 1;
        }

        switch ($option) {
            case 0:
                if ($this->confirm("¿Seguro que deseas quitar todos los roles del usuario: {$name}?")) {
                    foreach ($roles as $role) {
                        $uri_applicationRole = $role['uri_applicationRole'];
                        $response = ClientApi::deleteUserRole(uri_user: $uri_user, uri_applicationRole: $uri_applicationRole);
                        if (!$response['success']) {
                            $this->error("Error al eliminar el rol '{$uri_applicationRole}' del usuario: " . $response['error']);
                            return 1;
                        }
                    }
                    $this->info("¡Listo! Todos los roles del usuario '{$name}' han sido eliminados exitosamente.");
                } else {
                    $this->info('Operación cancelada.');
                }
                break;

            case 1:
                $choices = [];
                foreach ($roles as $rol) {
                    $label = "{$rol['name']} - {$rol['description']}";
                    $choices[$label] = $rol['uri_applicationRole'];
                }

                $selectedLabel = $this->choice(
                    '¿Qué rol deseas eliminar?',
                    array_keys($choices)
                );

                $selectedUri = $choices[$selectedLabel];
                $selectedRol = collect($roles)->firstWhere('uri_applicationRole', $selectedUri);
                $uriRol = $selectedRol['uri_applicationRole'] ?? null;

                if ($this->confirm("¿Seguro que deseas eliminar el rol <<{$selectedRol['name']}>> al usuario: {$name}?")) {
                    $response = ClientApi::deleteUserRole(uri_user: $uri_user, uri_applicationRole: $uriRol);
                    if (!$response['success']) {
                        $this->error("Error al eliminar el rol '{$uriRol}' del usuario: " . $response['error']);
                        return 1;
                    }
                    $this->info("¡Listo! El rol '{$selectedRol['name']}' ha sido eliminado del usuario seleccionado.");
                } else {
                    $this->info('Operación cancelada.');
                }
                break;

            case 2:
                $this->info('Operación cancelada.');
                return 0;

            default:
                $this->error('Opción no válida. Por favor, intenta de nuevo.');
                break;
        }

        return 0;
    }
}
