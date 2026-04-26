<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\DisabledManagementTestCase;

class RouteRegistrationWhenDisabledTest extends DisabledManagementTestCase
{
    public function test_management_routes_are_not_registered_when_disabled(): void
    {
        $this->assertTrue(Route::has('caronte.login.form'));
        $this->assertFalse(Route::has('caronte.management.dashboard'));
        $this->assertFalse(Route::has('caronte.management.users.show'));
    }
}
