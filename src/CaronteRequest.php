<?php

namespace Ometra\Caronte;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Ometra\Caronte\Api\CaronteHttpClient;
use Ometra\Caronte\Contracts\SendsPasswordRecovery;
use Ometra\Caronte\Contracts\SendsTwoFactorChallenge;
use Ometra\Caronte\Exceptions\CaronteApiException;
use Ometra\Caronte\Facades\Caronte;
use Ometra\Caronte\Support\CaronteResponse;
use Ometra\Caronte\Support\RequestContext;
use Symfony\Component\HttpFoundation\Response;

class CaronteRequest
{
    public static function userPasswordLogin(Request $request): Response
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $response = static::client()->authRequest(
                method: 'post',
                endpoint: 'api/auth/login',
                payload: [
                    'email' => $request->string('email')->toString(),
                    'password' => $request->string('password')->toString(),
                ],
            );

            $tokenString = (string) data_get($response, 'data.token', '');
            $token = CaronteToken::validateToken($tokenString, skipExchange: true);

            if (RequestContext::isWeb()) {
                Caronte::saveToken($token->toString());
            }

            return CaronteResponse::success(
                message: $response['message'],
                data: ['token' => $token->toString()],
                forwardUrl: static::forwardUrl($request->input('callback_url'))
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.LOGIN_URL')
            );
        }
    }

    public static function twoFactorTokenRequest(Request $request): Response
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $payload = [
                'email' => $request->string('email')->toString(),
                'app_url' => static::authBaseUrl(),
                'callback_url' => static::absoluteUrl(static::forwardUrl($request->input('callback_url'))),
            ];

            if (config('caronte.NOTIFICATION_DELIVERY') === 'host') {
                $response = static::client()->authRequest(
                    method: 'post',
                    endpoint: 'api/auth/2fa/issue',
                    payload: $payload,
                );

                app(SendsTwoFactorChallenge::class)->send(
                    email: (string) data_get($response, 'data.email', $request->string('email')->toString()),
                    actionUrl: (string) data_get($response, 'data.action_url', ''),
                    expiresAt: data_get($response, 'data.expires_at')
                );
            } else {
                $response = static::client()->authRequest(
                    method: 'post',
                    endpoint: 'api/auth/2fa',
                    payload: $payload,
                );
            }

            return CaronteResponse::success(
                message: $response['message'],
                data: $response['data'],
                forwardUrl: (string) config('caronte.LOGIN_URL')
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.LOGIN_URL')
            );
        }
    }

    public static function twoFactorTokenLogin(Request $request, string $token): Response
    {
        try {
            $response = static::client()->authRequest(
                method: 'post',
                endpoint: 'api/auth/2fa/' . $token,
            );

            $tokenString = (string) data_get($response, 'data.token', '');
            $validatedToken = CaronteToken::validateToken($tokenString, skipExchange: true);

            if (RequestContext::isWeb()) {
                Caronte::saveToken($validatedToken->toString());
            }

            return CaronteResponse::success(
                message: $response['message'],
                data: ['token' => $validatedToken->toString()],
                forwardUrl: static::forwardUrl($request->input('callback_url'))
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.LOGIN_URL')
            );
        }
    }

    public static function passwordRecoverRequest(Request $request): Response
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $payload = [
                'email' => $request->string('email')->toString(),
                'app_url' => static::authBaseUrl(),
            ];

            if (config('caronte.NOTIFICATION_DELIVERY') === 'host') {
                $response = static::client()->authRequest(
                    method: 'post',
                    endpoint: 'api/auth/password/recover/issue',
                    payload: $payload,
                );

                app(SendsPasswordRecovery::class)->send(
                    email: (string) data_get($response, 'data.email', $request->string('email')->toString()),
                    actionUrl: (string) data_get($response, 'data.action_url', ''),
                    expiresAt: data_get($response, 'data.expires_at')
                );
            } else {
                $response = static::client()->authRequest(
                    method: 'post',
                    endpoint: 'api/auth/password/recover',
                    payload: $payload,
                );
            }

            return CaronteResponse::success(
                message: $response['message'],
                data: $response['data'],
                forwardUrl: (string) config('caronte.LOGIN_URL')
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.LOGIN_URL')
            );
        }
    }

    public static function passwordRecoverTokenValidation(string $token): Response|View|InertiaResponse
    {
        try {
            $response = static::client()->authRequest(
                method: 'get',
                endpoint: 'api/auth/password/recover/' . $token,
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.LOGIN_URL')
            );
        }

        if (RequestContext::isApi()) {
            return CaronteResponse::success(
                message: $response['message'],
                data: $response['data']
            );
        }

        $view = config('caronte.USE_INERTIA') ? 'auth/password-recover' : 'auth.password-recover';

        if (config('caronte.USE_INERTIA')) {
            return inertia($view, [
                'csrf_token' => csrf_token(),
                'routes' => [
                    'passwordRecoverSubmit' => url()->current(),
                    'login' => config('caronte.LOGIN_URL'),
                ],
                'branding' => config('caronte.ui.branding'),
                'token' => $token,
            ]);
        }

        return view('caronte::' . $view, [
            'csrf_token' => csrf_token(),
            'routes' => [
                'passwordRecoverSubmit' => url()->current(),
                'login' => config('caronte.LOGIN_URL'),
            ],
            'branding' => config('caronte.ui.branding'),
            'token' => $token,
        ]);
    }

    public static function passwordRecover(Request $request, string $token): Response
    {
        $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        try {
            $response = static::client()->authRequest(
                method: 'post',
                endpoint: 'api/auth/password/recover/' . $token,
                payload: [
                    'password' => $request->string('password')->toString(),
                    'password_confirmation' => $request->string('password_confirmation')->toString(),
                ],
            );

            return CaronteResponse::success(
                message: $response['message'],
                data: $response['data'],
                forwardUrl: (string) config('caronte.LOGIN_URL')
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.LOGIN_URL')
            );
        }
    }

    public static function logout(bool $logoutAllSessions = false): Response
    {
        try {
            $response = static::client()->authRequest(
                method: 'post',
                endpoint: $logoutAllSessions ? 'api/auth/logoutAll' : 'api/auth/logout',
                userToken: Caronte::getToken()->toString(),
            );

            Caronte::clearToken();

            return CaronteResponse::success(
                message: $response['message'],
                data: $response['data'],
                forwardUrl: (string) config('caronte.LOGIN_URL')
            );
        } catch (CaronteApiException $exception) {
            return CaronteResponse::handleException(
                exception: $exception,
                errors: $exception->errors(),
                forwardUrl: (string) config('caronte.LOGIN_URL')
            );
        }
    }

    public static function setMetadata(Request $request): Response
    {
        try {
            $response = \Ometra\Caronte\Api\ClientApi::storeUserMetadata(
                uriUser: Caronte::getUser()->uri_user,
                metadata: $request->except(['_token']),
                tenantId: Caronte::getTenantId(),
            );

            return CaronteResponse::success(
                message: $response['message'],
                data: $response['data']
            );
        } catch (\Exception $exception) {
            return CaronteResponse::handleException($exception);
        }
    }

    private static function client(): CaronteHttpClient
    {
        /** @var CaronteHttpClient $client */
        $client = app(CaronteHttpClient::class);

        return $client;
    }

    private static function authBaseUrl(): string
    {
        $prefix = trim((string) config('caronte.ROUTES_PREFIX', ''), '/');

        return rtrim(url($prefix === '' ? '/' : $prefix), '/');
    }

    private static function forwardUrl(?string $candidate): string
    {
        $value = is_string($candidate) ? trim($candidate) : '';

        if ($value !== '') {
            $decoded = base64_decode($value, true);

            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }

            return $value;
        }

        return (string) config('caronte.SUCCESS_URL', '/');
    }

    private static function absoluteUrl(string $url): string
    {
        if (preg_match('/^https?:\/\//i', $url) === 1) {
            return $url;
        }

        return url($url);
    }
}
