<?php

/**
 * JWT token validation and exchange handler for Caronte authentication.
 *
 * PHP 8.1+
 *
 * @package   Ometra\Caronte
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/Ometra-Core/mx.ometra.caronte-client Documentation
 */

namespace Ometra\Caronte;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Ometra\Caronte\Facades\Caronte;
use Equidna\Toolkit\Exceptions\BadRequestException;
use Equidna\Toolkit\Exceptions\UnprocessableEntityException;

/**
 * Handles JWT token validation, decoding, and exchange operations.
 *
 * Provides secure token validation with configurable constraints including
 * signature verification, issuer validation, and expiration checks. Supports
 * automatic token exchange when tokens expire.
 *
 * @author Gabriel Ruelas
 * @license MIT
 * @version 1.4.0
 */
class CaronteToken
{
    public const MINIMUM_KEY_LENGTH = 32;

    /** @var bool Prevents infinite recursion during token exchange */
    private static bool $exchanging = false;

    private function __construct()
    {
        //
    }

    /**
     * Validate a JWT token string and return the validated token.
     *
     * @param  string $raw_token    Raw JWT token string.
     * @param  bool   $skipExchange Skip automatic token exchange on validation failure.
     * @return Plain Validated token instance.
     * @throws UnprocessableEntityException If the token is invalid or fails constraints.
     */
    public static function validateToken(string $raw_token, bool $skipExchange = false): Plain
    {
        $config = static::getConfig();
        $token  = static::decodeToken(raw_token: $raw_token);

        try {
            $config->validator()->assert(
                $token,
                ...static::getConstraints()
            );

            if (config('caronte.UPDATE_LOCAL_USER')) {
                Caronte::updateUserData($token->claims()->get('user'));
            }

            return $token;
        } catch (RequiredConstraintsViolated $e) {
            // Token validation failed - log violations and attempt exchange
            if (config('app.debug')) {
                foreach ($e->violations() as $violation) {
                    Log::debug('Token constraint violated: ' . $violation->getMessage());
                }
            }

            foreach ($e->violations() as $violation) {
                if (stripos($violation->getMessage(), 'issued in the future') !== false) {
                    $issuedAt = $token->claims()->get('iat');
                    if ($issuedAt instanceof DateTimeImmutable) {
                        $nowUtc     = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                        $skew       = $issuedAt->getTimestamp() - $nowUtc->getTimestamp();
                        $skewHuman  = $skew >= 0 ? '+' . $skew : (string) $skew;

                        Log::warning(
                            'JWT clock skew detected (iat is in the future).',
                            [
                                'iat'           => $issuedAt->format('c'),
                                'server_time'   => $nowUtc->format('c'),
                                'skew_seconds'  => $skewHuman,
                            ]
                        );
                    }

                    break;
                }
            }

            // Prevent infinite recursion and respect skipExchange flag
            if ($skipExchange || self::$exchanging) {
                throw new UnprocessableEntityException(
                    'Token validation failed. Please login again.',
                    previous: $e
                );
            }

            return static::exchangeToken(raw_token: $raw_token);
        }
    }

    /**
     * Exchange a raw token for a validated token using the Caronte API.
     *
     * @param  string $raw_token Raw JWT token string.
     * @return Plain Validated token instance.
     * @throws UnprocessableEntityException If the token exchange fails.
     */
    public static function exchangeToken(string $raw_token): Plain
    {
        // Prevent recursive exchange attempts
        if (self::$exchanging) {
            throw new UnprocessableEntityException('Token exchange already in progress');
        }

        self::$exchanging = true;

        try {
            $baseUrl = rtrim(config('caronte.URL'), '/');

            /** @var \Illuminate\Http\Client\Response $caronte_response */
            $caronte_response = Http::withOptions([
                'verify' => !config('caronte.ALLOW_HTTP_REQUESTS')
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $raw_token,
            ])->post(
                $baseUrl . '/api/user/exchange',
                [
                    'app_id' => config('caronte.APP_ID')
                ]
            );

            $caronte_response->throw();

            // Validate the exchanged token WITHOUT attempting another exchange
            $token = static::validateToken($caronte_response->body(), skipExchange: true);

            Caronte::saveToken($token->toString());
            Caronte::setTokenWasExchanged();

            return $token;
        } catch (RequestException $e) {
            Caronte::clearToken();
            throw new UnprocessableEntityException(
                'Cannot exchange token: ' . $e->getMessage(),
                previous: $e
            );
        } finally {
            self::$exchanging = false;
        }
    }

    /**
     * Decode a raw JWT token string.
     *
     * @param string $raw_token Raw JWT token string.
     * @return Plain Decoded token instance.
     * @throws BadRequestException|UnprocessableEntityException If the token is missing, malformed, or invalid.
     */
    public static function decodeToken(string $raw_token): Plain
    {
        if (empty($raw_token)) {
            throw new BadRequestException('Token not provided');
        }

        if (count(explode(".", $raw_token)) != 3) {
            throw new BadRequestException('Malformed token');
        }

        $token = static::getConfig()->parser()->parse($raw_token);

        if (!($token instanceof Plain)) {
            throw new BadRequestException('Invalid token');
        }

        if (!$token->claims()->has('user')) {
            throw new UnprocessableEntityException('Invalid token');
        }

        return $token;
    }

    /**
     * Get the JWT configuration for token operations.
     *
     * @return Configuration JWT configuration instance.
     * @throws \RuntimeException If the signing key is too short.
     */
    public static function getConfig(): Configuration
    {
        $signing_key = config('caronte.APP_SECRET');

        if (strlen($signing_key) < static::MINIMUM_KEY_LENGTH) {
            throw new \RuntimeException(
                sprintf(
                    'CARONTE_APP_SECRET must be at least %d characters long. Current length: %d. ' .
                        'Please update your .env file with a secure secret key.',
                    static::MINIMUM_KEY_LENGTH,
                    strlen($signing_key)
                )
            );
        }

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($signing_key)
        );

        return $config;
    }

    /**
     * Get the constraints for validating a JWT token.
     *
     * @return array Array of validation constraints.
     */
    public static function getConstraints(): array
    {
        $constraints = [];
        $constraints[] = new StrictValidAt(
            new SystemClock(new DateTimeZone('UTC'))
        );

        $config = static::getConfig();

        if (config('caronte.ENFORCE_ISSUER')) {
            $constraints[] = new IssuedBy(config('caronte.ISSUER_ID'));
        }

        $constraints[] = new SignedWith(
            $config->signer(),
            $config->signingKey()
        );

        return $constraints;
    }
}
