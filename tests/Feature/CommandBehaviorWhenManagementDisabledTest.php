<?php

namespace Tests\Feature;

use Tests\DisabledManagementTestCase;

class CommandBehaviorWhenManagementDisabledTest extends DisabledManagementTestCase
{
    public function test_user_management_commands_fail_fast_when_management_is_disabled(): void
    {
        $this->artisan('caronte:users:list', ['--tenant' => 'tenant-1'])
            ->expectsOutput('Caronte user management is disabled by config(caronte.management.enabled).')
            ->assertExitCode(1);

        $this->artisan('caronte:admin')
            ->expectsOutput('Caronte user management is disabled by config(caronte.management.enabled).')
            ->assertExitCode(1);
    }
}
