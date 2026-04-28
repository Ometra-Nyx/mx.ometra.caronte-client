<?php

namespace Ometra\Caronte\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Response as InertiaResponse;
use Ometra\Caronte\Api\AuthApi;
use Ometra\Caronte\Api\ClientApi;
use Ometra\Caronte\CaronteToken;
use Ometra\Caronte\Contracts\SendsPasswordRecovery;
use Ometra\Caronte\Contracts\SendsTwoFactorChallenge;
use Ometra\Caronte\Exceptions\CaronteApiException;
use Ometra\Caronte\Facades\Caronte;
use Ometra\Caronte\Support\CaronteResponse;
use Ometra\Caronte\Support\RouteMode;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseController
{
    public function loginForm(Request $request): View|InertiaResponse
    {
        $view = config('caronte.use_2fa') ? 'auth.two-factor' : 'auth.login';

        return $this->toView($view, [
            'callback_url' => $request->query('callback_url'),
            'csrf_token' => csrf_token(),
            'branding' => $this->branding(),
            'routes' => [
                'login' => route('caronte.login'),
                'logout' => route('caronte.logout'),
                'twoFactorRequest' => route('caronte.2fa.request'),
                'passwordRecoverForm' => route('caronte.password.recover.form'),
            ],
        ]);
    }

    public function login(Request $request): Response
    {
        if (config('caronte.use_2fa')) {
            return $this->handleTwoFactorTokenRequest($request);
        }

        return $this->handleUserPasswordLogin($request);
    }

    public function twoFactorTokenRequest(Request $request): Response
    {
        return $this->handleTwoFactorTokenRequest($request);
    }

    public function twoFactorTokenLogin(Request $request, string $token): Response
    {
        return $this->handleTwoFactorTokenLogin($request, $token);
    }

    public function passwordRecoverRequestForm(): View|InertiaResponse
    {
        return $this->toView('auth.password-recover-request', [
            'csrf_token' => csrf_token(),
            'branding' => $this->branding(),
            'routes' => [
                'passwordRecoverRequest' => route('caronte.password.recover.request'),
                'login' => route('caronte.login.form'),
            ],
        ]);
    }

    public function passwordRecoverRequest(Request $request): Response
    {
        return $this->handlePasswordRecoverRequest($request);
    }

    public function passwordRecoverTokenValidation(string $token): Response|View|InertiaResponse
    {
        return $this->handlePasswordRecoverTokenValidation($token);
    }

    public function passwordRecover(Request $request, string $token): Response
    {
        return $this->handlePasswordRecover($request, $token);
    }

    public function logout(Request $request): Response
    {
        return $this->handleLogout($request->boolean('all'));
    }

    private function handleUserPasswordLogin(Request $request): Response
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $response = AuthApi::login(
                email: $request->string('email')->toString(),
                password: $request->string('password')->toString()
            );

            $tokenString = (string) data_get($response, 'data.token', '');
            $token = CaronteToken::validateToken($tokenString, skipExchange: true);

            if (RouteMode::isWeb()) {
                Caronte::saveToken($token->toString());
            }

            return CaronteResponse::success(
                message: $response['message'],
                data: ['token' => $token->toString()],
                forwardUrl: $this->forwardUrl($request->input('callback_url'))
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.login_url')
            );
        }
    }

    private function handleTwoFactorTokenRequest(Request $request): Response
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $email = $request->string('email')->toString();
            $callbackUrl = $this->absoluteUrl($this->forwardUrl($request->input('callback_url')));

            if (config('caronte.notification_delivery') === 'host') {
                $response = AuthApi::issueTwoFactor(
                    email: $email,
                    callbackUrl: $callbackUrl
                );

                if ((string) data_get($response, 'data.action_url', '') === '') {
                    return CaronteResponse::success(
                        message: $response['message'],
                        data: $response['data'],
                        forwardUrl: (string) config('caronte.login_url')
                    );
                }

                app(SendsTwoFactorChallenge::class)->send(
                    email: (string) data_get($response, 'data.email', $request->string('email')->toString()),
                    actionUrl: (string) data_get($response, 'data.action_url', ''),
                    expiresAt: data_get($response, 'data.expires_at')
                );
            } else {
                $response = AuthApi::requestTwoFactor(
                    email: $email,
                    callbackUrl: $callbackUrl
                );
            }

            return CaronteResponse::success(
                message: $response['message'],
                data: $response['data'],
                forwardUrl: (string) config('caronte.login_url')
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.login_url')
            );
        }
    }

    private function handleTwoFactorTokenLogin(Request $request, string $token): Response
    {
        try {
            $response = AuthApi::consumeTwoFactor($token);

            $tokenString = (string) data_get($response, 'data.token', '');
            $validatedToken = CaronteToken::validateToken($tokenString, skipExchange: true);

            if (RouteMode::isWeb()) {
                Caronte::saveToken($validatedToken->toString());
            }

            return CaronteResponse::success(
                message: $response['message'],
                data: ['token' => $validatedToken->toString()],
                forwardUrl: $this->forwardUrl($request->input('callback_url'))
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.login_url')
            );
        }
    }

    private function handlePasswordRecoverRequest(Request $request): Response
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $email = $request->string('email')->toString();

            if (config('caronte.notification_delivery') === 'host') {
                $response = AuthApi::issuePasswordRecovery(
                    email: $email
                );

                if ((string) data_get($response, 'data.action_url', '') === '') {
                    return CaronteResponse::success(
                        message: $response['message'],
                        data: $response['data'],
                        forwardUrl: (string) config('caronte.login_url')
                    );
                }

                app(SendsPasswordRecovery::class)->send(
                    email: (string) data_get($response, 'data.email', $request->string('email')->toString()),
                    actionUrl: (string) data_get($response, 'data.action_url', ''),
                    expiresAt: data_get($response, 'data.expires_at')
                );
            } else {
                $response = AuthApi::requestPasswordRecovery(
                    email: $email
                );
            }

            return CaronteResponse::success(
                message: $response['message'],
                data: $response['data'],
                forwardUrl: (string) config('caronte.login_url')
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.login_url')
            );
        }
    }

    private function handlePasswordRecoverTokenValidation(string $token): Response|View|InertiaResponse
    {
        try {
            $response = AuthApi::validatePasswordRecovery($token);
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.login_url')
            );
        }

        if (RouteMode::wantsJson()) {
            return CaronteResponse::success(
                message: $response['message'],
                data: $response['data']
            );
        }

        $view = config('caronte.use_inertia') ? 'auth/password-recover' : 'auth.password-recover';

        if (config('caronte.use_inertia')) {
            return inertia($view, [
                'csrf_token' => csrf_token(),
                'routes' => [
                    'passwordRecoverSubmit' => url()->current(),
                    'login' => config('caronte.login_url'),
                ],
                'branding' => config('caronte.ui.branding'),
                'token' => $token,
            ]);
        }

        return view('caronte::' . $view, [
            'csrf_token' => csrf_token(),
            'routes' => [
                'passwordRecoverSubmit' => url()->current(),
                'login' => config('caronte.login_url'),
            ],
            'branding' => config('caronte.ui.branding'),
            'token' => $token,
        ]);
    }

    private function handlePasswordRecover(Request $request, string $token): Response
    {
        $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        try {
            $response = AuthApi::resetPassword(
                token: $token,
                password: $request->string('password')->toString(),
                passwordConfirmation: $request->string('password_confirmation')->toString()
            );

            return CaronteResponse::success(
                message: $response['message'],
                data: $response['data'],
                forwardUrl: (string) config('caronte.login_url')
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.login_url')
            );
        }
    }

    private function handleLogout(bool $logoutAllSessions = false): Response
    {
        try {
            $response = AuthApi::logout(
                allSessions: $logoutAllSessions
            );

            Caronte::clearToken();

            return CaronteResponse::success(
                message: $response['message'],
                data: $response['data'],
                forwardUrl: (string) config('caronte.login_url')
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.login_url')
            );
        }
    }

    private function forwardUrl(?string $candidate): string
    {
        $value = is_string($candidate) ? trim($candidate) : '';

        if ($value !== '') {
            $decoded = base64_decode($value, true);

            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }

            return $value;
        }

        return (string) config('caronte.success_url', '/');
    }

    private function absoluteUrl(string $url): string
    {
        if (preg_match('/^https?:\/\//i', $url) === 1) {
            return $url;
        }

        return url($url);
    }
}
