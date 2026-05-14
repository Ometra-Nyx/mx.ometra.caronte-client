<?php

namespace Tests\Feature;

use DateTimeImmutable;
use DateTimeZone;
use Equidna\BeeHive\Tenancy\TenantContext;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Ometra\Caronte\Support\CaronteApplicationContext;
use Ometra\Caronte\Support\CaronteApplicationAccessContext;
use Ometra\Caronte\Support\CaronteApplicationToken;
use Tests\TestCase;

class MiddlewareBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['caronte.application:tenant_required'])
            ->get('/api/_caronte/application-check', fn() => response()->json(['ok' => true]));

        Route::middleware(['caronte.application:tenant_required'])
            ->get('/api/_caronte/context-check', function (Request $request) {
                /** @var CaronteApplicationContext $context */
                $context = app(CaronteApplicationContext::class);

                return response()->json([
                    'tenant_context' => app(TenantContext::class)->get(),
                    'app_id' => $context->appId,
                ]);
            });

        Route::middleware(['caronte.application'])
            ->get('/api/_caronte/application-only-check', function () {
                /** @var CaronteApplicationContext $context */
                $context = app(CaronteApplicationContext::class);

                return response()->json([
                    'app_id' => $context->appId,
                    'tenant_context' => app()->bound(TenantContext::class)
                        ? app(TenantContext::class)->get()
                        : null,
                ]);
            });

        Route::middleware(['caronte.session'])
            ->get('/api/_caronte/session-check', fn() => response()->json(['ok' => true]));

        Route::middleware(['web', 'caronte.session'])
            ->get('/_caronte/session-check', function () {
                return response()->json([
                    'tenant_context' => app()->bound(TenantContext::class)
                        ? app(TenantContext::class)->get()
                        : null,
                ]);
            });

        Route::middleware(['caronte.session', 'caronte.roles:admin'])
            ->get('/api/_caronte/role-check', fn() => response()->json(['ok' => true]));

        Route::middleware(['caronte.app-token', 'caronte.app-permissions:invoices.read'])
            ->get('/api/_caronte/application-access-check', function () {
                /** @var CaronteApplicationAccessContext $context */
                $context = app(CaronteApplicationAccessContext::class);

                return response()->json([
                    'tenant_id' => $context->tenantId,
                    'permissions' => $context->permissions,
                ]);
            });
    }

    public function test_application_middleware_requires_tenant_when_requested(): void
    {
        $this->getJson('/api/_caronte/application-check')
            ->assertStatus(401);

        $this->getJson('/api/_caronte/application-check', [
            'X-Application-Token' => CaronteApplicationToken::make(),
        ])->assertStatus(400);

        $this->getJson('/api/_caronte/application-check', [
            'X-Application-Token' => CaronteApplicationToken::make(),
            'X-Tenant-Id' => 'tenant-1',
        ])->assertOk();
    }

    public function test_application_middleware_accepts_optional_tenant_context(): void
    {
        $this->getJson('/api/_caronte/application-only-check', [
            'X-Application-Token' => CaronteApplicationToken::make(),
        ])
            ->assertOk()
            ->assertJsonPath('app_id', CaronteApplicationToken::appId())
            ->assertJsonPath('tenant_context', null);
    }

    public function test_application_middleware_accepts_group_application_token(): void
    {
        config()->set('caronte.application_group_id', 'core-suite');
        config()->set('caronte.application_group_secret', 'group-secret-with-minimum-length-32');

        $this->getJson('/api/_caronte/application-only-check', [
            'X-Application-Token' => CaronteApplicationToken::makeGroup(),
        ])
            ->assertOk()
            ->assertJsonPath('app_id', CaronteApplicationToken::appId());

        /** @var CaronteApplicationContext $context */
        $context = app(CaronteApplicationContext::class);
        $this->assertTrue($context->authenticatedAsGroup);
        $this->assertSame('core-suite', $context->groupId);
    }

    public function test_application_middleware_binds_tenant_context_for_the_request_lifecycle(): void
    {
        $this->getJson('/api/_caronte/context-check', [
            'X-Application-Token' => CaronteApplicationToken::make(),
            'X-Tenant-Id' => 'tenant-1',
        ])
            ->assertOk()
            ->assertJsonPath('tenant_context', 'tenant-1')
            ->assertJsonPath('app_id', CaronteApplicationToken::appId());
    }

    public function test_single_tenant_application_middleware_binds_configured_tenant_without_header(): void
    {
        config()->set('caronte.tenancy.mode', 'single');
        config()->set('caronte.tenancy.tenant_id', 'mobig');

        $this->getJson('/api/_caronte/context-check', [
            'X-Application-Token' => CaronteApplicationToken::make(),
        ])
            ->assertOk()
            ->assertJsonPath('tenant_context', 'mobig')
            ->assertJsonPath('app_id', CaronteApplicationToken::appId());
    }

    public function test_single_tenant_application_middleware_rejects_header_mismatch(): void
    {
        config()->set('caronte.tenancy.mode', 'single');
        config()->set('caronte.tenancy.tenant_id', 'mobig');

        $this->getJson('/api/_caronte/application-only-check', [
            'X-Application-Token' => CaronteApplicationToken::make(),
            'X-Tenant-Id' => 'other-tenant',
        ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tenant mismatch.');
    }

    public function test_application_middleware_rejects_header_that_overrides_authenticated_tenant(): void
    {
        $token = $this->makeToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/_caronte/context-check', [
                'X-Application-Token' => CaronteApplicationToken::make(),
                'X-Tenant-Id' => 'other-tenant',
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tenant override is not allowed.');
    }

    public function test_session_middleware_exchanges_expired_api_tokens_and_returns_the_refreshed_header(): void
    {
        $expired = $this->makeToken(
            issuedAt: new DateTimeImmutable('-30 minutes', new DateTimeZone('UTC')),
            expiresAt: new DateTimeImmutable('-5 minutes', new DateTimeZone('UTC')),
        );
        $fresh = $this->makeToken();

        Http::fake([
            'https://caronte.test/api/auth/exchange' => Http::response([
                'status' => 200,
                'message' => 'Token exchanged',
                'data' => ['token' => $fresh],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $expired)
            ->getJson('/api/_caronte/session-check');

        $response->assertOk();
        $response->assertHeader('X-User-Token', $fresh);
    }

    public function test_role_middleware_rejects_users_without_the_required_role(): void
    {
        $token = $this->makeToken([
            'uri_user' => 'user-1',
            'name' => 'Viewer',
            'email' => 'viewer@example.com',
            'tenant_id' => 'tenant-1',
            'roles' => [
                [
                    'name' => 'rootless',
                    'app_id' => CaronteApplicationToken::appId(),
                    'uri_applicationRole' => sha1(CaronteApplicationToken::appId() . 'rootless'),
                ],
            ],
            'metadata' => [],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/_caronte/role-check')
            ->assertStatus(403);
    }

    public function test_web_session_middleware_preserves_application_permission_error_detail(): void
    {
        $token = $this->makeToken([
            'uri_user' => 'user-1',
            'name' => 'Foreign App User',
            'email' => 'foreign@example.com',
            'tenant_id' => 'tenant-1',
            'roles' => [
                [
                    'name' => 'viewer',
                    'app_id' => 'other-app-id',
                    'uri_applicationRole' => sha1('other-app-id' . 'viewer'),
                ],
            ],
            'metadata' => [],
        ]);

        $this->withSession([(string) config('caronte.session_key', 'caronte.user_token') => $token])
            ->from('/dashboard')
            ->get('/_caronte/session-check')
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'general' => 'User does not have access to this application.',
            ])
            ->assertSessionMissing((string) config('caronte.session_key', 'caronte.user_token'));
    }

    public function test_single_tenant_session_middleware_binds_configured_tenant(): void
    {
        config()->set('caronte.tenancy.mode', 'single');
        config()->set('caronte.tenancy.tenant_id', 'mobig');

        $token = $this->makeToken([
            'uri_user' => 'user-1',
            'name' => 'Mobig User',
            'email' => 'mobig@example.com',
            'tenant_id' => 'mobig',
            'roles' => [
                [
                    'name' => 'root',
                    'app_id' => CaronteApplicationToken::appId(),
                    'uri_applicationRole' => sha1(CaronteApplicationToken::appId() . 'root'),
                ],
            ],
            'metadata' => [],
        ]);

        $this->withSession([(string) config('caronte.session_key', 'caronte.user_token') => $token])
            ->getJson('/_caronte/session-check')
            ->assertOk()
            ->assertJsonPath('tenant_context', 'mobig');
    }

    public function test_single_tenant_session_middleware_rejects_token_without_tenant(): void
    {
        config()->set('caronte.tenancy.mode', 'single');
        config()->set('caronte.tenancy.tenant_id', 'mobig');

        $token = $this->makeToken([
            'uri_user' => 'user-1',
            'name' => 'Global User',
            'email' => 'global@example.com',
            'tenant_id' => null,
            'roles' => [
                [
                    'name' => 'root',
                    'app_id' => CaronteApplicationToken::appId(),
                    'uri_applicationRole' => sha1(CaronteApplicationToken::appId() . 'root'),
                ],
            ],
            'metadata' => [],
        ]);

        $this->withSession([(string) config('caronte.session_key', 'caronte.user_token') => $token])
            ->getJson('/_caronte/session-check')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tenant is required for this application.')
            ->assertSessionMissing((string) config('caronte.session_key', 'caronte.user_token'));
    }

    public function test_single_tenant_session_middleware_rejects_token_tenant_mismatch(): void
    {
        config()->set('caronte.tenancy.mode', 'single');
        config()->set('caronte.tenancy.tenant_id', 'mobig');

        $token = $this->makeToken();

        $this->withSession([(string) config('caronte.session_key', 'caronte.user_token') => $token])
            ->getJson('/_caronte/session-check')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tenant mismatch.')
            ->assertSessionMissing((string) config('caronte.session_key', 'caronte.user_token'));
    }

    public function test_application_access_middleware_accepts_tokens_with_required_permission(): void
    {
        $token = $this->makeApplicationAccessToken(['invoices.read']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/_caronte/application-access-check')
            ->assertOk()
            ->assertJsonPath('tenant_id', 'tenant-1')
            ->assertJsonPath('permissions.0', 'invoices.read');
    }

    public function test_application_access_middleware_rejects_missing_permission(): void
    {
        $token = $this->makeApplicationAccessToken(['invoices.write']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/_caronte/application-access-check')
            ->assertStatus(403);
    }
}
