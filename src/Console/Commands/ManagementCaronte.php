<?php

namespace Ometra\Caronte\Console\Commands;

use Illuminate\Console\Command;
use Ometra\Caronte\Console\Concerns\GuardsManagement;

class ManagementCaronte extends Command
{
    use GuardsManagement;

    protected $signature = 'caronte:admin';

    protected $description = 'Interactive entry point for Caronte user management commands.';

    public function handle(): int
    {
        if (!$this->ensureManagementEnabled()) {
            return self::FAILURE;
        }

        do {
            $selected = $this->choice('Choose an operation', [
                'Sync configured roles',
                'List users',
                'Create user',
                'Update user',
                'Delete user',
                'Sync user roles',
                'Exit',
            ]);

            match ($selected) {
                'Sync configured roles' => $this->call('caronte:roles:sync'),
                'List users' => $this->call('caronte:users:list'),
                'Create user' => $this->call('caronte:users:create'),
                'Update user' => $this->call('caronte:users:update'),
                'Delete user' => $this->call('caronte:users:delete'),
                'Sync user roles' => $this->call('caronte:users:roles:sync'),
                'Exit' => null,
            };

            if ($selected === 'Exit') {
                break;
            }
        } while (true);

        return self::SUCCESS;
    }
}
