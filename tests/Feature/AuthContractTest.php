<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Ometra\Caronte\Mail\PasswordRecoveryMail;
use Ometra\Caronte\Mail\TwoFactorChallengeMail;
use Ometra\Caronte\Support\ApplicationToken;
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
        $this->assertSame($token, session(config('caronte.SESSION_KEY')));

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://caronte.test/api/auth/login'
                && $request->hasHeader('X-Application-Token', ApplicationToken::make())
                && $request['email'] === 'root@example.com'
                && $request['password'] === 'Password123!';
        });
    }

    public function test_host_notification_delivery_uses_issue_endpoints_and_package_mailables(): void
    {
        config()->set('caronte.NOTIFICATION_DELIVERY', 'host');

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
    }
}
