<?php

/**
 * Console command for interactive user management operations.
 *
 * PHP 8.1+
 *
 * @package   Ometra\Caronte\Console\Commands
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/Ometra-Core/mx.ometra.caronte-client Documentation
 */

namespace Ometra\Caronte\Console\Commands;

use Illuminate\Console\Command;
use Ometra\Caronte\Api\ClientApi;

class ManagementUsers extends Command
{
    protected $signature   = 'caronte-client:management-users';
    protected $description = 'Manages users within the application';

    public function handle(): int
    {
        $mainOptions = [
            '0' => 'Crear Usuario',
            '1' => 'Editar Usuario',
            '2' => 'Eliminar Usuario',
            '3' => 'Salir',
        ];
        $editOptions = [
            '0' => 'Cambiar nombre',
            '1' => 'Gestionar roles',
            '2' => 'Ver roles asignados',
            '3' => 'Volver',
        ];

        do {
            $selectedOption = $this->choice(
                'Selecciona una opción:',
                array_values($mainOptions)
            );
            $option = array_search($selectedOption, $mainOptions);

            switch ($option) {
                case '0':
                    $this->call('caronte-client:create-user');
                    break;

                case '1':
                    $this->info('A continuación se mostrará la lista de usuarios que puedes editar...');

                    $response = ClientApi::showUsers(paramSearch: '', usersApp: true);
                    $users = json_decode($response['data'], true);

                    if (empty($users)) {
                        $this->warn("No se encontraron usuarios en esta aplicación");
                        break;
                    }

                    $options = [];
                    $lookupMap = [];

                    foreach ($users as $user) {
                        $key = "{$user['name']} - (uri_user: {$user['uri_user']})";
                        $options[] = $key;
                        $lookupMap[$key] = ['uri' => $user['uri_user'], 'name' => $user['name']];
                    }

                    $selectedUser = $this->choice(
                        '¿Qué usuario deseas editar?',
                        $options
                    );
                    $selectedUserId = $lookupMap[$selectedUser]['uri'];
                    $userName = $lookupMap[$selectedUser]['name'];

                    do {
                        $selectedEditOption = $this->choice(
                            "Editando a: {$userName}",
                            array_values($editOptions)
                        );
                        $editOption = array_search($selectedEditOption, $editOptions);

                        switch ($editOption) {
                            case '0':
                                $this->call('caronte-client:update-user', [
                                    'uri_user'  => $selectedUserId,
                                    'name_user' => $userName,
                                ]);
                                break;

                            case '1':
                                $this->call('caronte-client:delete-user-roles', [
                                    'uri_user'  => $selectedUserId,
                                    'name_user' => $userName,
                                ]);
                                break;

                            case '2':
                                $this->call('caronte-client:show-user-roles', [
                                    'uri_user' => $selectedUserId,
                                ]);
                                break;

                            case '3':
                                $this->info('Volviendo al menú principal...');
                                break 2;

                            default:
                                $this->error('Opción no válida. Por favor, intenta de nuevo.');
                                break;
                        }
                    } while (true);
                    break;

                case '2':
                    $this->info('A continuación se mostrará la lista de usuarios que puedes eliminar...');

                    $response = ClientApi::showUsers(paramSearch: '', usersApp: true);
                    $users = json_decode($response['data'], true);

                    if (empty($users)) {
                        $this->warn("No se encontraron usuarios en esta aplicación");
                        break;
                    }

                    $options = [];
                    $lookupMap = [];

                    foreach ($users as $user) {
                        $key = "{$user['name']} - (uri_user: {$user['uri_user']})";
                        $options[] = $key;
                        $lookupMap[$key] = $user['uri_user'];
                    }

                    $selectedUser = $this->choice(
                        '¿Qué usuario deseas eliminar?',
                        $options
                    );
                    $selectedUserId = $lookupMap[$selectedUser];

                    if ($this->confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
                        $this->call('caronte-client:delete-user', ['uri_user' => $selectedUserId]);
                    }
                    break;

                case '3':
                    $this->info('Saliendo del gestor de usuarios...');
                    return 0;

                default:
                    $this->error('Opción no válida. Por favor, intenta de nuevo.');
                    break;
            }
        } while (true);
    }
}
