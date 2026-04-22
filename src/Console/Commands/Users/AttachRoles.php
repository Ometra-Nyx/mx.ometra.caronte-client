<?php

/**
 * Console command to attach roles to users interactively.
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
use Ometra\Caronte\CaronteRoleManager;

class AttachRoles extends Command
{
    protected $signature   = 'caronte-client:attach-roles';
    protected $description = 'Associates a specific role with a user';

    public function handle(): int
    {
        $response = ClientApi::showUsers(paramSearch: '');
        $users = json_decode($response['data'], true);

        if (empty($users)) {
            $this->error("No se encontraron usuarios");
            return 1;
        }

        $options = [];
        $lookupMap = [];

        foreach ($users as $user) {
            $key = "{$user['name']} - (uri_user: {$user['uri_user']})";
            $options[] = $key;
            $lookupMap[$key] = $user['uri_user'];
        }

        $selectedOption = $this->choice(
            '¿Qué usuario estás buscando?',
            $options
        );
        $selectedUserId = $lookupMap[$selectedOption];
        $userSelect = collect($users)->firstWhere('uri_user', $selectedUserId);

        $response = CaronteRoleManager::getRoles();
        $roles = $response;
        if (empty($roles)) {
            $this->warn("No hay roles registrados.");
            return 0;
        }

        $choices = [];
        $choicesValues = [];
        foreach ($roles as $rol) {
            $label = "{$rol['name']} - {$rol['description']}";
            $choices[$label] = $rol['uri_applicationRole'];
            $choicesValues[] = $label;
        }

        $selectedLabel = $this->choice(
            'Escribe el rol que quieres enlazar',
            $choicesValues
        );

        $selectedUri = $choices[$selectedLabel];
        $selectedRol = collect($roles)->firstWhere('uri_applicationRole', $selectedUri);
        $uriRol = $selectedRol['uri_applicationRole'] ?? null;

        if (!$selectedRol) {
            $this->error("Rol no encontrado.");
            return 1;
        }

        $this->info("Has seleccionado: {$selectedRol['name']}");

        if ($this->confirm("Seguro que deseas asignar el rol <<{$selectedRol['name']}>> al usuario: {$userSelect['name']}?")) {
            $response = ClientApi::assignRoleToUser(
                uriUser: $selectedUserId,
                uriApplicationRole: $uriRol
            );
            if (!$response['success']) {
                $this->error("Error al asignar el rol: " . $response['error']);
                return 1;
            }
            $this->info("¡Listo! El rol '{$selectedRol['name']}' ha sido asignado al usuario seleccionado.");
        } else {
            $this->info('Operación cancelada.');
        }

        return 0;
    }
}
