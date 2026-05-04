<?php

namespace Ometra\Caronte;

use DateTimeImmutable;
use DateTimeZone;
use Equidna\Toolkit\Exceptions\BadRequestException;
use Equidna\Toolkit\Exceptions\UnprocessableEntityException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Ometra\Caronte\Support\CaronteApplicationAccessContext;
use Ometra\Caronte\Support\CaronteApplicationToken;
use RuntimeException;

final class CaronteApplicationAccessToken
{
    public const MINIMUM_KEY_LENGTH = 32;

    public static function validateToken(string $rawToken): CaronteApplicationAccessContext
    {
        $token = static::decodeToken($rawToken);
        static::assertSignatureAndIssuer($token);
        static::assertApplicationClaim($token);
        static::assertTemporalClaims($token);

        $permissions = $token->claims()->get('permissions');

        if (! is_array($permissions)) {
            throw new UnprocessableEntityException('Invalid application token permissions.');
        }

        return new CaronteApplicationAccessContext(
            tokenId: (string) $token->claims()->get('jti'),
            appId: (string) $token->claims()->get('app_id'),
            tenantId: (string) $token->claims()->get('tenant_id'),
            name: (string) $token->claims()->get('name', ''),
            permissions: collect($permissions)
                ->map(fn(mixed $permission): string => strtolower(trim((string) $permission)))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        );
    }

    public static function decodeToken(string $rawToken): Plain
    {
        if ($rawToken === '') {
            throw new BadRequestException('Application token not provided');
        }

        if (count(explode('.', $rawToken)) !== 3) {
            throw new BadRequestException('Malformed application token');
        }

        $token = static::getConfig()->parser()->parse($rawToken);

        if (!$token instanceof Plain) {
            throw new BadRequestException('Invalid application token');
        }

        foreach (['jti', 'app_id', 'tenant_id', 'permissions'] as $claim) {
            if (!$token->claims()->has($claim)) {
                throw new UnprocessableEntityException('Invalid application token');
            }
        }

        if ((string) $token->claims()->get('token_audience', '') !== 'application_token') {
            throw new UnprocessableEntityException('Invalid application token audience.');
        }

        return $token;
    }

    public static function getConfig(): Configuration
    {
        $signingKey = (string) config('caronte.app_secret');

        if (mb_strlen($signingKey) < static::MINIMUM_KEY_LENGTH) {
            throw new RuntimeException(
                sprintf(
                    'CARONTE_APP_SECRET must be at least %d characters long. Current length: %d.',
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
        $config = static::getConfig();
        $constraints = [
            new SignedWith($config->signer(), $config->signingKey()),
        ];

        if (config('caronte.enforce_issuer')) {
            $constraints[] = new IssuedBy((string) config('caronte.issuer_id'));
        }

        if (!$config->validator()->validate($token, ...$constraints)) {
            throw new UnprocessableEntityException('Invalid application token signature or issuer.');
        }
    }

    private static function assertApplicationClaim(Plain $token): void
    {
        if ((string) $token->claims()->get('app_id') !== CaronteApplicationToken::appId()) {
            throw new UnprocessableEntityException('Application token does not match the configured Caronte application.');
        }

        if ((string) $token->claims()->get('tenant_id', '') === '') {
            throw new UnprocessableEntityException('Application token tenant is required.');
        }
    }

    private static function assertTemporalClaims(Plain $token): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        foreach (['iat', 'nbf'] as $claim) {
            if (!$token->claims()->has($claim)) {
                continue;
            }

            $value = $token->claims()->get($claim);

            if ($value instanceof DateTimeImmutable && $value > $now) {
                throw new UnprocessableEntityException('Application token is not yet valid.');
            }
        }

        $expiresAt = $token->claims()->get('exp');

        if ($expiresAt instanceof DateTimeImmutable && $expiresAt <= $now) {
            throw new UnprocessableEntityException('Application token has expired.');
        }
    }
}
