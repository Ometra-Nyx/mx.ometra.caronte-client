<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Ometra\Caronte\Support\CaronteApplicationToken;
use Tests\DisabledManagementTestCase;
use Tests\TestCase;

class CommandBehaviorTest extends TestCase
{
    public function test_roles_sync_dry_run_does_not_push_remote_changes(): void
    {
        Http::fake([
            'https://caronte.test/api/applications/roles' => Http::response([
                'status' => 200,
                'message' => 'Roles retrieved',
                'data' => [],
            ], 200),
        ]);

        $this->artisan('caronte:roles:sync', ['--dry-run' => true])
            ->expectsOutput('Dry run completed. No remote changes were sent.')
            ->assertExitCode(0);

        Http::assertSentCount(1);
        Http::assertSent(fn($request): bool => $request->method() === 'GET');
    }

    public function test_roles_sync_command_normalizes_configured_roles_and_calls_sync_endpoint(): void
    {
        Http::fake([
            'https://caronte.test/api/applications/roles' => Http::response([
                'status' => 200,
                'message' => 'Roles synchronized',
                'data' => [],
            ], 200),
        ]);

        $this->artisan('caronte:roles:sync')
            ->expectsOutput('Roles synchronized')
            ->assertExitCode(0);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://caronte.test/api/applications/roles'
                && $request->method() === 'PUT'
                && $request->hasHeader('X-Application-Token', CaronteApplicationToken::make())
                && in_array('root', array_column($request['roles'], 'name'), true)
                && in_array('admin', array_column($request['roles'], 'name'), true);
        });
    }

    public function test_user_list_command_requires_tenant_and_uses_new_endpoint_contract(): void
    {
        Http::fake([
            'https://caronte.test/api/users*' => Http::response([
                'status' => 200,
                'message' => 'Users retrieved',
                'data' => [
                    ['uri_user' => 'user-1', 'name' => 'Jane Doe', 'email' => 'jane@example.com'],
                ],
            ], 200),
        ]);

        $this->artisan('caronte:users:list', ['--tenant' => 'tenant-1'])
            ->expectsTable(['URI', 'Name', 'Email'], [['user-1', 'Jane Doe', 'jane@example.com']])
            ->assertExitCode(0);

        Http::assertSent(function ($request): bool {
            return str_starts_with($request->url(), 'https://caronte.test/api/users')
                && $request->hasHeader('X-Application-Token', CaronteApplicationToken::make())
                && $request->hasHeader('X-Tenant-Id', 'tenant-1');
        });
    }

    public function test_user_create_command_creates_the_user_and_syncs_configured_roles(): void
    {
        Http::fake([
            'https://caronte.test/api/users' => Http::response([
                'status' => 200,
                'message' => 'User created',
                'data' => [
                    'user' => [
                        'uri_user' => 'user-9',
                        'name' => 'Jane Doe',
                        'email' => 'jane@example.com',
                    ],
                ],
            ], 200),
            'https://caronte.test/api/users/user-9/roles' => Http::response([
                'status' => 200,
                'message' => 'Roles synchronized',
                'data' => [],
            ], 200),
        ]);

        $this->artisan('caronte:users:create', [
            '--tenant' => 'tenant-1',
            '--name' => 'Jane Doe',
            '--email' => 'jane@example.com',
            '--password' => 'Password123!',
            '--role' => ['admin'],
        ])
            ->expectsQuestion('Confirm password', 'Password123!')
            ->expectsOutput('User created')
            ->assertExitCode(0);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://caronte.test/api/users'
                && $request->method() === 'POST'
                && $request->hasHeader('X-Tenant-Id', 'tenant-1')
                && $request['email'] === 'jane@example.com';
        });

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://caronte.test/api/users/user-9/roles'
                && $request->method() === 'PUT'
                && $request->hasHeader('X-Tenant-Id', 'tenant-1')
                && $request['roles'] === [sha1(CaronteApplicationToken::appId() . 'admin')];
        });
    }
}
