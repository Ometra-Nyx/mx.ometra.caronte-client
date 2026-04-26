<?php

namespace Tests;

abstract class DisabledManagementTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('caronte.management.enabled', false);
    }
}
