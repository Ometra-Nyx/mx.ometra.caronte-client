<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\MessageBag;
use Ometra\Caronte\Api\CaronteApiClient;
use Ometra\Caronte\Caronte;
use Ometra\Caronte\CaronteUserToken;
use Ometra\Caronte\Contracts\SendsPasswordRecovery;
use Ometra\Caronte\Contracts\SendsTwoFactorChallenge;
use Ometra\Caronte\Mail\PasswordRecoveryMail;
use Ometra\Caronte\Mail\TwoFactorChallengeMail;
use Ometra\Caronte\Models\CaronteUser;
use Ometra\Caronte\Support\CaronteApplicationToken;
use Tests\TestCase;

class AuthContractTest extends TestCase
{
    public function test_login_uses_current_caronte_headers_and_persists_token_in_session(): void
    {
        $token = $this->makeToken();

        Http::fake([
            'https://caronte.test/api/auth/login' => Http::response([
                'status' => 200,
                'message' => 'Token generated',
                'data' => ['token' => $token],
            ], 200),
        ]);

        $response = $this->post('/login', [
            'email' => 'root@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/');
        $this->assertSame($token, session(config('caronte.session_key')));

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://caronte.test/api/auth/login'
                && $request->hasHeader('X-Application-Token', CaronteApplicationToken::make())
                && $request['email'] === 'root@example.com'
                && $request['password'] === 'Password123!';
        });
    }

    public function test_login_redirects_with_tenant_options_when_selection_is_required(): void
    {
        Http::fake([
            'https://caronte.test/api/auth/login' => Http::response([
                'status' => 409,
                'message' => 'Tenant selection required.',
                'errors' => [
                    'code' => 'tenant_selection_required',
                    'tenants' => [
                        ['tenant_id' => 'tenant-a', 'name' => 'Tenant A', 'global' => false],
                        ['tenant_id' => 'tenant-b', 'name' => 'Tenant B', 'global' => false],
                    ],
                ],
            ], 409),
        ]);

        $response = $this->post('/login', [
            'email' => 'shared@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('data.tenants.0.tenant_id', 'tenant-a');
        $response->assertSessionHasErrors(['code']);
    }

    public function test_login_sends_selected_tenant_to_caronte(): void
    {
        $token = $this->makeToken();

        Http::fake([
            'https://caronte.test/api/auth/login' => Http::response([
                'status' => 200,
                'message' => 'Token generated',
                'data' => ['token' => $token],
            ], 200),
        ]);

        $this->post('/login', [
            'email' => 'shared@example.com',
            'password' => 'Password123!',
            'tenant_id' => 'tenant-b',
        ])->assertRedirect('/');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://caronte.test/api/auth/login'
                && $request['email'] === 'shared@example.com'
                && $request['password'] === 'Password123!'
                && $request['tenant_id'] === 'tenant-b';
        });
    }

    public function test_local_user_sync_persists_token_tenant(): void
    {
        Schema::dropIfExists('Users');
        Schema::create('Users', function (Blueprint $table): void {
            $table->string('id_tenant', 64)->nullable()->index();
            $table->string('uri_user', 40)->primary();
            $table->string('name', 150);
            $table->string('email', 150);
        });

        Caronte::updateUserData((object) [
            'uri_user' => 'user-123',
            'name' => 'Root User',
            'email' => 'root@example.com',
            'id_tenant' => 'tenant-1',
            'metadata' => [],
        ]);

        $this->assertSame(
            'tenant-1',
            CaronteUser::withoutGlobalScopes()->whereKey('user-123')->value('id_tenant')
        );
    }

    public function test_host_notification_delivery_uses_issue_endpoints_and_package_mailables(): void
    {
        config()->set('caronte.notification_delivery', 'host');

        Http::fake([
            'https://caronte.test/api/auth/2fa/issue' => Http::response([
                'status' => 200,
                'message' => '2FA challenge issued',
                'data' => [
                    'email' => 'root@example.com',
                    'action_url' => 'https://client.test/2fa/example-token',
                    'expires_at' => '2026-04-25T10:00:00Z',
                ],
            ], 200),
            'https://caronte.test/api/auth/password/recover/issue' => Http::response([
                'status' => 200,
                'message' => 'Recovery issued',
                'data' => [
                    'email' => 'root@example.com',
                    'action_url' => 'https://client.test/password/recover/example-token',
                    'expires_at' => '2026-04-25T10:00:00Z',
                ],
            ], 200),
        ]);

        Mail::fake();

        $this->post('/2fa', ['email' => 'root@example.com'])->assertRedirect('/login');
        $this->post('/password/recover', ['email' => 'root@example.com'])->assertRedirect('/login');

        Mail::assertSent(TwoFactorChallengeMail::class);
        Mail::assertSent(PasswordRecoveryMail::class);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://caronte.test/api/auth/2fa/issue'
                && $request['email'] === 'root@example.com'
                && isset($request['callback_url'])
                && ! array_key_exists('app_url', $request->data());
        });

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://caronte.test/api/auth/password/recover/issue'
                && $request['email'] === 'root@example.com'
                && ! array_key_exists('app_url', $request->data());
        });
    }

    public function test_package_auth_views_render_without_explicit_branding(): void
    {
        $routes = [
            'login' => '/login',
            'passwordRecoverForm' => '/password/recover',
            'passwordRecoverRequest' => '/password/recover',
            'passwordRecoverSubmit' => '/password/recover/token',
            'twoFactorRequest' => '/2fa',
        ];

        $this->assertStringContainsString('Sign in', view('caronte::auth.login', [
            'routes' => $routes,
            'callback_url' => null,
        ])->render());

        $this->assertStringContainsString('Password recovery', view('caronte::auth.password-recover-request', [
            'routes' => $routes,
        ])->render());
    }

    public function test_package_mailables_render_with_string_expiration(): void
    {
        $expiresAt = '2026-04-25T10:00:00Z';

        $this->assertStringContainsString(
            $expiresAt,
            (new PasswordRecoveryMail('https://client.test/password/recover/example-token', $expiresAt))->render()
        );

        $this->assertStringContainsString(
            $expiresAt,
            (new TwoFactorChallengeMail('https://client.test/2fa/example-token', $expiresAt))->render()
        );
    }

    public function test_notification_senders_are_resolved_from_configuration(): void
    {
        config()->set('caronte.notifications.two_factor_sender', TestTwoFactorChallengeSender::class);
        config()->set('caronte.notifications.password_recovery_sender', TestPasswordRecoverySender::class);

        TestTwoFactorChallengeSender::$sent = [];
        TestPasswordRecoverySender::$sent = [];

        app(SendsTwoFactorChallenge::class)->send(
            email: 'root@example.com',
            actionUrl: 'https://client.test/2fa/example-token',
            expiresAt: '2026-04-25T10:00:00Z'
        );

        app(SendsPasswordRecovery::class)->send(
            email: 'root@example.com',
            actionUrl: 'https://client.test/password/recover/example-token',
            expiresAt: '2026-04-25T10:00:00Z'
        );

        $this->assertSame([
            'email' => 'root@example.com',
            'actionUrl' => 'https://client.test/2fa/example-token',
            'expiresAt' => '2026-04-25T10:00:00Z',
        ], TestTwoFactorChallengeSender::$sent);

        $this->assertSame([
            'email' => 'root@example.com',
            'actionUrl' => 'https://client.test/password/recover/example-token',
            'expiresAt' => '2026-04-25T10:00:00Z',
        ], TestPasswordRecoverySender::$sent);
    }

    public function test_user_request_injects_current_user_token(): void
    {
        $token = $this->makeToken();

        Route::middleware('web')->get('/_caronte/user-request-check', function () {
            app(CaronteApiClient::class)->userRequest(
                method: 'get',
                endpoint: 'api/auth/current-user'
            );

            return response('ok');
        });

        Http::fake([
            'https://caronte.test/api/auth/current-user' => Http::response([
                'status' => 200,
                'message' => 'Current user retrieved',
                'data' => [],
            ], 200),
        ]);

        $this->withSession([
            config('caronte.session_key') => $token,
        ])->get('/_caronte/user-request-check')->assertOk();

        Http::assertSent(function ($request) use ($token): bool {
            return $request->url() === 'https://caronte.test/api/auth/current-user'
                && !$request->hasHeader('X-Application-Token')
                && $request->hasHeader('X-User-Token', $token);
        });
    }

    public function test_group_user_token_validates_with_group_secret_and_group_id(): void
    {
        config()->set('caronte.application_group_id', 'core-suite');
        config()->set('caronte.application_group_secret', 'group-secret-with-minimum-length-32');

        $token = $this->makeToken(group: true);
        $parsed = CaronteUserToken::validateToken($token);

        $this->assertSame('application_group', $parsed->claims()->get('token_audience'));
        $this->assertSame('core-suite', $parsed->claims()->get('group_id'));
        $this->assertSame(sha1('source-app'), $parsed->claims()->get('source_app_id'));
    }

    public function test_flash_partial_deduplicates_error_messages(): void
    {
        session()->flash('error', 'Token not found');
        session()->flash('message', 'Token not found');

        $html = view('caronte::partials.messages', [
            'errors' => (new ViewErrorBag())->put('default', new MessageBag([
                'general' => 'Token not found',
            ])),
        ])->render();

        $this->assertSame(1, substr_count($html, 'alert alert-danger'));
        $this->assertSame(0, substr_count($html, 'alert alert-info'));
    }
}

class TestTwoFactorChallengeSender implements SendsTwoFactorChallenge
{
    /** @var array{email?: string, actionUrl?: string, expiresAt?: string|null} */
    public static array $sent = [];

    public function send(string $email, string $actionUrl, ?string $expiresAt = null): void
    {
        self::$sent = compact('email', 'actionUrl', 'expiresAt');
    }
}

class TestPasswordRecoverySender implements SendsPasswordRecovery
{
    /** @var array{email?: string, actionUrl?: string, expiresAt?: string|null} */
    public static array $sent = [];

    public function send(string $email, string $actionUrl, ?string $expiresAt = null): void
    {
        self::$sent = compact('email', 'actionUrl', 'expiresAt');
    }
}
