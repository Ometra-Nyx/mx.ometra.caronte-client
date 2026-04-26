<?php

namespace Tests\Feature;

use InvalidArgumentException;
use Ometra\Caronte\Support\ConfiguredRoles;
use Tests\TestCase;

class ConfigurationValidationTest extends TestCase
{
    public function test_management_access_roles_must_exist_in_configured_roles(): void
    {
        config()->set('caronte.roles', [
            'root' => 'Default super administrator role',
        ]);
        config()->set('caronte.management.access_roles', ['operator']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown management access role [operator].');

        ConfiguredRoles::validate();
    }

    public function test_invalid_role_names_are_rejected(): void
    {
        config()->set('caronte.roles', [
            ['name' => 'Bad Role!', 'description' => 'Invalid'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('contains invalid characters');

        ConfiguredRoles::all();
    }

    public function test_root_role_is_always_present_after_normalization(): void
    {
        config()->set('caronte.roles', [
            'admin' => 'Administrative access',
        ]);

        $roles = ConfiguredRoles::keyedByName();

        $this->assertArrayHasKey('admin', $roles);
        $this->assertArrayHasKey('root', $roles);
        $this->assertSame('Default super administrator role', $roles['root']['description']);
    }
}
