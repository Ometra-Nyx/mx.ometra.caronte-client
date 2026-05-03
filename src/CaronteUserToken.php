<?php

namespace Ometra\Caronte;

use DateTimeImmutable;
use DateTimeZone;
use Equidna\Toolkit\Exceptions\BadRequestException;
use Equidna\Toolkit\Exceptions\UnprocessableEntityException;
use Illuminate\Support\Arr;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Ometra\Caronte\Api\AuthApi;
use Ometra\Caronte\Exceptions\CaronteApiException;
use Ometra\Caronte\Facades\Caronte;
use Ometra\Caronte\Oidc\Base64Url;
use Ometra\Caronte\Oidc\OidcTokenValidator;
use Ometra\Caronte\Support\CaronteApplicationToken;
use Ometra\Caronte\Support\RouteMode;
use RuntimeException;

final class CaronteUserToken
{
    public const MINIMUM_KEY_LENGTH = 32;

    private static bool $exchanging = false;

    public static function validateToken(string $rawToken, bool $skipExchange = false): Plain
    {
        if (static::shouldUseOidc($rawToken)) {
            return app(OidcTokenValidator::class)->validate($rawToken);
        }

        $token = static::decodeToken($rawToken);

        static::assertSignatureAndIssuer($token);
        static::assertApplicationClaim($token);

        if (static::isExpired($token)) {
            if ($skipExchange || static::$exchanging) {
                throw new UnprocessableEntityException('Token has expired. Please login again.');
            }

            return static::exchangeToken($rawToken);
        }

        static::assertNotBefore($token);

        if (config('caronte.update_local_user')) {
            Caronte::updateUserData((string) $token->claims()->get('user'));
        }

        return $token;
    }

    public static function exchangeToken(string $rawToken): Plain
    {
        if (static::$exchanging) {
            throw new UnprocessableEntityException('Token exchange already in progress.');
        }

        static::$exchanging = true;

        try {
            $response = AuthApi::exchange($rawToken);

            $tokenString = Arr::get($response, 'data.token');

            if (!is_string($tokenString) || $tokenString === '') {
                throw new UnprocessableEntityException('Caronte did not return a refreshed token.');
            }

            $token = static::validateToken($tokenString, skipExchange: true);

            if (RouteMode::isWeb()) {
                Caronte::saveToken($token->toString());
            }

            Caronte::setTokenWasExchanged();

            return $token;
        } catch (CaronteApiException $exception) {
            Caronte::clearToken();

            throw new UnprocessableEntityException(
                'Cannot exchange token: ' . $exception->getMessage(),
                previous: $exception
            );
        } finally {
            static::$exchanging = false;
        }
    }

    public static function decodeToken(string $rawToken): Plain
    {
        if ($rawToken === '') {
            throw new BadRequestException('Token not provided');
        }

        if (count(explode('.', $rawToken)) !== 3) {
            throw new BadRequestException('Malformed token');
        }

        $token = static::getConfig()->parser()->parse($rawToken);

        if (!$token instanceof Plain) {
            throw new BadRequestException('Invalid token');
        }

        if (!$token->claims()->has('user')) {
            throw new UnprocessableEntityException('Invalid token');
        }

        if (!$token->claims()->has('app_id') && !$token->claims()->has('group_id')) {
            throw new UnprocessableEntityException('Invalid token');
        }

        return $token;
    }

    private static function shouldUseOidc(string $rawToken): bool
    {
        $mode = (string) config('caronte.auth_mode', 'legacy');

        if ($mode === 'legacy') {
            return false;
        }

        if ($mode === 'oidc') {
            return true;
        }

        $parts = explode('.', $rawToken);

        if (count($parts) !== 3) {
            return false;
        }

        $header = json_decode((string) Base64Url::decode($parts[0]), true);

        return is_array($header) && isset($header['kid']);
    }

    public static function getConfig(): Configuration
    {
        return static::configForSigningKey(
            signingKey: (string) config('caronte.app_secret'),
            configName: 'CARONTE_APP_SECRET'
        );
    }

    public static function getGroupConfig(): Configuration
    {
        return static::configForSigningKey(
            signingKey: (string) config('caronte.application_group_secret'),
            configName: 'CARONTE_APPLICATION_GROUP_SECRET'
        );
    }

    private static function configForSigningKey(string $signingKey, string $configName): Configuration
    {

        if (mb_strlen($signingKey) < static::MINIMUM_KEY_LENGTH) {
            throw new RuntimeException(
                sprintf(
                    '%s must be at least %d characters long. Current length: %d.',
                    $configName,
                    static::MINIMUM_KEY_LENGTH,
                    mb_strlen($signingKey)
                )
            );
        }

        return Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($signingKey)
        );
    }

    private static function assertSignatureAndIssuer(Plain $token): void
    {
        $config = static::configForToken($token);
        $validator = $config->validator();
        $constraints = [
            new SignedWith($config->signer(), $config->signingKey()),
        ];

        if (config('caronte.enforce_issuer')) {
            $constraints[] = new IssuedBy((string) config('caronte.issuer_id'));
        }

        if (!$validator->validate($token, ...$constraints)) {
            throw new UnprocessableEntityException('Invalid token signature or issuer.');
        }
    }

    private static function assertApplicationClaim(Plain $token): void
    {
        $audience = (string) $token->claims()->get('token_audience', 'application');

        if ($audience === 'application_group') {
            $groupId = (string) $token->claims()->get('group_id', '');

            if ($groupId === '' || $groupId !== CaronteApplicationToken::groupId()) {
                throw new UnprocessableEntityException('Token application group does not match the configured Caronte application group.');
            }

            return;
        }

        $appId = (string) $token->claims()->get('app_id');
        if ($appId !== CaronteApplicationToken::appId()) {
            throw new UnprocessableEntityException('Token application does not match the configured Caronte application.');
        }
    }

    private static function assertNotBefore(Plain $token): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        foreach (['iat', 'nbf'] as $claim) {
            if (!$token->claims()->has($claim)) {
                continue;
            }

            $value = $token->claims()->get($claim);

            if ($value instanceof DateTimeImmutable && $value > $now) {
                throw new UnprocessableEntityException('Token is not yet valid.');
            }
        }
    }

    private static function isExpired(Plain $token): bool
    {
        if (!$token->claims()->has('exp')) {
            return false;
        }

        $expiresAt = $token->claims()->get('exp');

        return $expiresAt instanceof DateTimeImmutable
            && $expiresAt <= new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    private static function configForToken(Plain $token): Configuration
    {
        $audience = (string) $token->claims()->get('token_audience', 'application');

        return $audience === 'application_group'
            ? static::getGroupConfig()
            : static::getConfig();
    }
}
