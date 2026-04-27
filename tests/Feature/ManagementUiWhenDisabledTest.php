<?php

namespace Tests\Feature;

use Tests\DisabledManagementTestCase;

class ManagementUiWhenDisabledTest extends DisabledManagementTestCase
{
    public function test_management_dashboard_is_not_reachable_when_management_is_disabled(): void
    {
        $this->get('/caronte/management')->assertNotFound();
    }
}
