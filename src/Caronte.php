<?php

/**
 * @author Gabriel Ruelas
 * @license MIT
 * @version 1.4.0
 */

namespace Ometra\Caronte;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cookie;
use Lcobucci\JWT\Token\Plain;
use Ometra\Caronte\Models\CaronteUser;
use Ometra\Caronte\Exceptions\TenantMissingException;
use Equidna\Toolkit\Exceptions\UnauthorizedException;
use Equidna\Toolkit\Helpers\RouteHelper;
use Exception;
use stdClass;

class Caronte
{
    public const COOKIE_NAME = 'caronte_token';
    private bool $new_token  = false;

    public function __construct()
    {
        //
    }

    public function checkToken(): bool
    {
        $token_str = RouteHelper::isAPI() ? request()->bearerToken() : $this->webToken();
        if (is_null($token_str) || empty($token_str)) {
            return false;
        }
        return true;
    }

    /**
     * Retrieve and validate the JWT token from the request or storage.
     *
     * @return Plain Validated JWT token instance.
     * @throws UnauthorizedException If the token is missing or invalid.
     */
    public function getToken(): Plain
    {
        $token_str = RouteHelper::isAPI() ? request()->bearerToken() : $this->webToken();
        if (is_null($token_str) || empty($token_str)) {
            throw new UnauthorizedException('Token not found');
        }

        return CaronteToken::validateToken(raw_token: $token_str);
    }

    /**
     * Get the user object from the JWT token claims.
     *
     * @return stdClass Decoded user object from token claims.
     * @throws UnauthorizedException If user claim is missing or invalid.
     */
    public function getUser(): stdClass
    {
        try {
            return json_decode($this->getToken()->claims()->get('user'));
        } catch (Exception $e) {
            throw new UnauthorizedException(
                message: 'No user provided',
                errors: [$e->getMessage()],
                previous: $e
            );
        }
    }

    /**
     * Get the tenant identifier from the user JWT claim.
     *
     * @return string Tenant identifier (id_tenant).
     * @throws TenantMissingException If id_tenant is missing or invalid.
     * @throws UnauthorizedException Propagated from getUser() when token or user claim is invalid.
     */
    public function getTenantId(): string
    {
        $user = $this->getUser();

        if (!isset($user->id_tenant) || $user->id_tenant === null || $user->id_tenant === '') {
            throw new TenantMissingException('No tenant provided');
        }

        return (string) $user->id_tenant;
    }

    /**
     * Get the URI user parameter from the current route.
     *
     * @return string URI user value or empty string if not present.
     */
    public static function getRouteUser(): string
    {
        return request()->route('uri_user') ?: '';
    }

    /**
     * Save the token string and associate it with a persistent token ID in a cookie.
     *
     * @param string $token_str Token string to store.
     * @return void
     */
    public function saveToken(string $token_str): void
    {
        //If cookie doesn't have an existent token id value, we generate one new random string
        $token_id = Cookie::get(static::COOKIE_NAME) ?: Str::random(20);

        Cookie::queue(Cookie::forever(static::COOKIE_NAME, $token_id));

        //if an old token is stored we clear the file first
        if (Storage::disk('local')->exists('tokens/' . $token_id)) {
            Storage::disk('local')->delete('tokens/' . $token_id);
        }

        Storage::disk('local')->put('tokens/' . $token_id, $token_str);
    }

    /**
     * Clear the stored token and remove the cookie.
     *
     * @return void
     */
    public function clearToken(): void
    {
        $this->forgetCookie();
    }

    /**
     * Mark that the token was exchanged for a new one.
     *
     * @return void
     */
    public function setTokenWasExchanged(): void
    {
        $this->new_token = true;
    }

    /**
     * Check if the token was exchanged during the request lifecycle.
     *
     * @return bool True if token was exchanged, false otherwise.
     */
    public function tokenWasExchanged(): bool
    {
        return $this->new_token;
    }

    /**
     * Echoes the given message.
     *
     * @param string $message The message to be echoed.
     * @return string The echoed message.
     */
    public function echo(string $message): string
    {
        return $message;
    }

    /**
     * Get the web token from storage.
     *
     * @return null|string
     */
    private function webToken(): ?string
    {
        if (Storage::disk('local')->exists('tokens/' . Cookie::get(static::COOKIE_NAME))) {
            return Storage::disk('local')->get('tokens/' . Cookie::get(static::COOKIE_NAME));
        }

        return  null;
    }

    /**
     * Deletes the token cookie and removes the corresponding token file from the local storage.
     *
     * @return void
     */
    private function forgetCookie(): void
    {
        if (Storage::disk('local')->exists('tokens/' . Cookie::get(static::COOKIE_NAME))) {
            Storage::disk('local')->delete('tokens/' . Cookie::get(static::COOKIE_NAME));
        }

        Cookie::queue(Cookie::forget(static::COOKIE_NAME));
    }

    /**
     * Update local user data.
     *
     * @param stdClass $user The user object containing updated data.
     * @return void
     */
    public static function updateUserData(stdClass|string $user): void
    {
        if (is_string($user)) {
            $user = json_decode($user);
        }

        try {
            $tenantId = isset($user->id_tenant) && $user->id_tenant !== ''
                ? (string) $user->id_tenant
                : null;

            $local_user = CaronteUser::updateOrCreate(
                [
                    'uri_user' => $user->uri_user
                ],
                [
                    'id_tenant' => $tenantId,
                    'name'  => $user->name,
                    'email' => $user->email
                ]
            );

            foreach ($user->metadata as $metadata) {
                $local_user->metadata()->updateOrCreate(
                    [
                        'uri_user'  => $user->uri_user,
                        'key'       => $metadata->key,
                    ],
                    [
                        'value'     => $metadata->value,
                        'scope'     => $metadata->scope ?: config('caronte.APP_ID')
                    ]
                );
            }
        } catch (Exception $e) {
            // If this error exists it's because the users migration was not run
            // No need to log it, just ignore it
        }
    }
}
