<?php

namespace Ometra\Caronte;

use Equidna\Toolkit\Exceptions\UnauthorizedException;
use Exception;
use Lcobucci\JWT\Token\Plain;
use Ometra\Caronte\Exceptions\TenantMissingException;
use Ometra\Caronte\Models\CaronteUser;
use Ometra\Caronte\CaronteUserToken;
use Ometra\Caronte\Support\RouteMode;
use stdClass;

final class Caronte
{
    private bool $newToken = false;

    public function checkToken(): bool
    {
        try {
            $this->getToken();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getToken(): Plain
    {
        $token = $this->rawToken();

        if (!is_string($token) || $token === '') {
            throw new UnauthorizedException('Token not found');
        }

        return CaronteUserToken::validateToken($token);
    }

    public function getUser(): stdClass
    {
        try {
            $token = $this->getToken();

            if ($token->claims()->has('user')) {
                $user = json_decode((string) $token->claims()->get('user'));
            } else {
                $subject = (string) $token->claims()->get('sub', '');
                $subjectParts = explode(':', $subject, 2);
                $user = (object) [
                    'uri_user' => count($subjectParts) === 2 ? $subjectParts[1] : $subject,
                    'name' => (string) $token->claims()->get('name', ''),
                    'email' => (string) $token->claims()->get('email', ''),
                    'tenant_id' => $token->claims()->get('tenant_id'),
                    'id_tenant' => $token->claims()->get('tenant_id'),
                    'roles' => array_map(
                        fn(string $role): object => (object) ['name' => $role],
                        is_array($token->claims()->get('roles', [])) ? $token->claims()->get('roles', []) : []
                    ),
                    'metadata' => [],
                ];
            }

            if (!$user instanceof stdClass) {
                throw new \RuntimeException('Invalid user payload.');
            }

            return $user;
        } catch (Exception $exception) {
            throw new UnauthorizedException(
                message: 'No user provided',
                errors: [$exception->getMessage()],
                previous: $exception
            );
        }
    }

    public function getTenantId(): string
    {
        $user = $this->getUser();

        if (!isset($user->id_tenant) || $user->id_tenant === null || $user->id_tenant === '') {
            throw new TenantMissingException('No tenant provided');
        }

        return (string) $user->id_tenant;
    }

    public static function getRouteUser(): string
    {
        return (string) (request()->route('uri_user') ?: '');
    }

    public function saveToken(string $token): void
    {
        if (!app()->bound('session')) {
            return;
        }

        request()->session()->put((string) config('caronte.session_key', 'caronte.user_token'), $token);
    }

    public function clearToken(): void
    {
        if (!app()->bound('session')) {
            return;
        }

        request()->session()->forget((string) config('caronte.session_key', 'caronte.user_token'));
    }

    public function setTokenWasExchanged(): void
    {
        $this->newToken = true;
    }

    public function tokenWasExchanged(): bool
    {
        return $this->newToken;
    }

    public function echo(string $message): string
    {
        return $message;
    }

    public static function updateUserData(stdClass|string $user): void
    {
        if (is_string($user)) {
            $user = json_decode($user);
        }

        if (!$user instanceof stdClass) {
            return;
        }

        try {
            $tenantId = isset($user->id_tenant) && $user->id_tenant !== ''
                ? (string) $user->id_tenant
                : null;

            $localUser = CaronteUser::updateOrCreate(
                [
                    'uri_user' => $user->uri_user,
                ],
                [
                    'id_tenant' => $tenantId,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            );

            $metadata = is_iterable($user->metadata ?? null) ? $user->metadata : [];

            foreach ($metadata as $item) {
                if (!isset($item->key)) {
                    continue;
                }

                $localUser->metadata()->updateOrCreate(
                    [
                        'uri_user' => $user->uri_user,
                        'key' => $item->key,
                    ],
                    [
                        'value' => $item->value ?? null,
                        'scope' => $item->scope ?? \Ometra\Caronte\Support\CaronteApplicationToken::appId(),
                    ]
                );
            }
        } catch (Exception) {
            // Local sync is optional and should never break authentication flows.
        }
    }

    private function rawToken(): ?string
    {
        if (RouteMode::wantsJson()) {
            return request()->bearerToken();
        }

        if (!app()->bound('session')) {
            return null;
        }

        return request()->session()->get((string) config('caronte.session_key', 'caronte.user_token'));
    }
}
