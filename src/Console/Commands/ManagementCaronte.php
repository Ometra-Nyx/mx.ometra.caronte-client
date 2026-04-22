<?php

/**
 * Root management command for CLI system administration.
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

class ManagementCaronte extends Command
{
    protected $signature = 'caronte-client:management';
    protected $description = 'Entry point for managing caronte-client commands';

    public function handle(): int
    {
        $this->line('Sistema de comandos de adminitración de caronte:');
        $mainOptions = [
            '0' => 'Administración de usuarios',
            '1' => 'Administración de roles',
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
                    $this->call('caronte-client:management-users');
                    break;
                case '1':
                    $this->call('caronte-client:management-roles');
                    break;
                case '2':
                    $this->info('Saliendo del administrador principal...');
                    return 0;
                default:
                    $this->error('Opción no válida. Por favor, intenta de nuevo.');
                    break;
            }
        } while (true);
        return 0;
    }
}
