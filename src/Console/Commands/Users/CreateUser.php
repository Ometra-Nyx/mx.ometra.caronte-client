<?php

/**
 * Console command to create users interactively with password prompting.
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

class CreateUser extends Command
{
    protected $signature   = 'caronte-client:create-user';
    protected $description = 'Create Users within the application';

    public function handle(): int
    {
        $name  = $this->ask('Escribe el nombre del usuario:');
        $email = $this->ask('Escribe el Email del usuario:');

        $password = '';
        $password_confirmation = '';

        do {
            $password = $this->secret('Escribe la contraseña:');
            $password_confirmation = $this->secret('Confirma la contraseña:');
            if ($password != $password_confirmation) {
                $this->info('las contraseñas no coinciden');
            }
        } while ($password != $password_confirmation);

        $response = ClientApi::createUser(name: $name, email: $email, password: $password, password_confirmation: $password_confirmation);

        if (!$response['success']) {
            $this->error("Error al crear el usuario: " . $response['error']);
            return 1;
        }

        $this->info("¡Listo! El usuario '{$name}' ha sido creado exitosamente.");

        return 0;
    }
}
