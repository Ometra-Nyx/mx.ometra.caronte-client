<?php

namespace Ometra\Caronte\Console\Commands;

use Illuminate\Console\Command;
use Ometra\Caronte\CaronteRoleManager;

class ManagementRoles extends Command
{
    protected $signature = 'caronte-client:management-roles';
    protected $description = 'Manages roles within the application';

    public function handle(): int
    {
        $mainOptions = [
            '0' => 'Crear nuevo rol',
            '1' => 'Gestionar un rol existente',
            '2' => 'Ver roles existentes',
            '3' => 'Salir',
        ];
        $optionsRoles = [
            '0' => 'Editar rol',
            '1' => 'Eliminar rol',
            '2' => 'Salir',
        ];
        do {
            $selectedOption = $this->choice(
                'Selecciona una opción:',
                array_values($mainOptions)
            );
            $option = array_search($selectedOption, $mainOptions);
            switch ($option) {
                case '0':
                    $this->call('caronte-client:create-role');
                    break;
                case '1':
                    do {
                        $roles = CaronteRoleManager::getRoles();
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
                            'Escribe el rol que quieres gestionar',
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

                        $selectedOptionRoles = $this->choice(
                            'Selecciona una opción:',
                            array_values($optionsRoles)
                        );
                        $optionRole = array_search($selectedOptionRoles, $optionsRoles);
                        switch ($optionRole) {
                            case '0':
                                $this->call('caronte-client:update-role', ['uri_rol' => $uriRol]);
                                break;
                            case '1':
                                $this->call('caronte-client:delete-role', ['uri_rol' => $uriRol]);
                                break;
                            case '2':
                                $this->info('Regresando al menú principal...');
                                break 2;
                            default:
                                $this->error('Opción no válida. Por favor, intenta de nuevo.');
                                break;
                        }
                    } while (true);
                    break;
                case '2':
                    $this->call('caronte-client:show-roles');
                    break;
                case '3':
                    $this->info('Saliendo del gestor de roles...');
                    return 0;
                default:
                    $this->error('Opción no válida. Por favor, intenta de nuevo.');
                    break;
            }
        } while (true);
    }
}
