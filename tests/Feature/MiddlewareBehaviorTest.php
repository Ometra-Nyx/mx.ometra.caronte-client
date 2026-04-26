<?php

namespace Tests\Feature;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Ometra\Caronte\Http\Context\CaronteApplicationContext;
use Ometra\Caronte\Support\ApplicationToken;
use Tests\TestCase;

class MiddlewareBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['caronte.application', 'caronte.tenant'])
            ->get('/api/_caronte/application-check', fn() => response()->json(['ok' => true]));

        Route::middleware(['caronte.application', 'caronte.tenant'])
            ->get('/api/_caronte/context-check', function (Request $request) {
                /** @var CaronteApplicationContext $context */
                $context = app(CaronteApplicationContext::class);

                return response()->json([
                    'tenant_attribute' => $request->attributes->get('tenant_id'),
                    'tenant_context' => $context->tenantId,
                    'app_id' => $context->appId,
                ]);
            });

        Route::middleware(['caronte.session'])
            ->get('/api/_caronte/session-check', fn() => response()->json(['ok' => true]));

        Route::middleware(['caronte.session', 'caronte.roles:admin'])
            ->get('/api/_caronte/role-check', fn() => response()->json(['ok' => true]));
    }

    public function test_application_and_tenant_middlewares_require_the_expected_headers(): void
    {
        $this->getJson('/api/_caronte/application-check')
            ->assertStatus(401);

        $this->getJson('/api/_caronte/application-check', [
            'X-Application-Token' => ApplicationToken::make(),
        ])->assertStatus(400);

        $this->getJson('/api/_caronte/application-check', [
            'X-Application-Token' => ApplicationToken::make(),
            'X-Tenant-Id' => 'tenant-1',
        ])->assertOk();
    }

    public function test_tenant_middleware_binds_tenant_context_for_the_request_lifecycle(): void
    {
        $this->getJson('/api/_caronte/context-check', [
            'X-Application-Token' => ApplicationToken::make(),
            'X-Tenant-Id' => 'tenant-1',
        ])
            ->assertOk()
            ->assertJsonPath('tenant_attribute', 'tenant-1')
            ->assertJsonPath('tenant_context', 'tenant-1')
            ->assertJsonPath('app_id', ApplicationToken::appId());
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
            'id_tenant' => 'tenant-1',
            'roles' => [
                [
                    'name' => 'rootless',
                    'app_id' => ApplicationToken::appId(),
                    'uri_applicationRole' => sha1(ApplicationToken::appId() . 'rootless'),
                ],
            ],
            'metadata' => [],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/_caronte/role-check')
            ->assertStatus(403);
    }
}
