<?php

namespace Ometra\Caronte\Api;

class AuthApi
{
    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function login(string $email, string $password, ?string $tenantId = null): array
    {
        $payload = [
            'email' => $email,
            'password' => $password,
        ];

        if (is_string($tenantId) && trim($tenantId) !== '') {
            $payload['tenant_id'] = trim($tenantId);
        }

        return app(CaronteApiClient::class)->authRequest(
            method: 'post',
            endpoint: 'api/auth/login',
            payload: $payload
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function requestTwoFactor(string $email, string $callbackUrl): array
    {
        return app(CaronteApiClient::class)->authRequest(
            method: 'post',
            endpoint: 'api/auth/2fa',
            payload: [
                'email' => $email,
                'callback_url' => $callbackUrl,
            ]
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function issueTwoFactor(string $email, string $callbackUrl): array
    {
        return app(CaronteApiClient::class)->authRequest(
            method: 'post',
            endpoint: 'api/auth/2fa/issue',
            payload: [
                'email' => $email,
                'callback_url' => $callbackUrl,
            ]
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function consumeTwoFactor(string $token): array
    {
        return app(CaronteApiClient::class)->authRequest(
            method: 'post',
            endpoint: 'api/auth/2fa/' . $token
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function requestPasswordRecovery(string $email): array
    {
        return app(CaronteApiClient::class)->authRequest(
            method: 'post',
            endpoint: 'api/auth/password/recover',
            payload: [
                'email' => $email,
            ]
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function issuePasswordRecovery(string $email): array
    {
        return app(CaronteApiClient::class)->authRequest(
            method: 'post',
            endpoint: 'api/auth/password/recover/issue',
            payload: [
                'email' => $email,
            ]
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function validatePasswordRecovery(string $token): array
    {
        return app(CaronteApiClient::class)->authRequest(
            method: 'get',
            endpoint: 'api/auth/password/recover/' . $token
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function resetPassword(string $token, string $password, string $passwordConfirmation): array
    {
        return app(CaronteApiClient::class)->authRequest(
            method: 'post',
            endpoint: 'api/auth/password/recover/' . $token,
            payload: [
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ]
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function logout(?string $userToken = null, bool $allSessions = false): array
    {
        if ($userToken === null) {
            return app(CaronteApiClient::class)->userRequest(
                method: 'post',
                endpoint: $allSessions ? 'api/auth/logoutAll' : 'api/auth/logout'
            );
        }

        return app(CaronteApiClient::class)->authRequest(
            method: 'post',
            endpoint: $allSessions ? 'api/auth/logoutAll' : 'api/auth/logout',
            userToken: $userToken
        );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public static function exchange(string $userToken): array
    {
        return app(CaronteApiClient::class)->authRequest(
            method: 'post',
            endpoint: 'api/auth/exchange',
            userToken: $userToken
        );
    }
}
