<?php

namespace Ometra\Caronte\Oidc;

use RuntimeException;

final class Jwk
{
    public static function toPem(array $jwk): string
    {
        $modulus = Base64Url::decode((string) ($jwk['n'] ?? ''));
        $exponent = Base64Url::decode((string) ($jwk['e'] ?? ''));

        if (! is_string($modulus) || ! is_string($exponent)) {
            throw new RuntimeException('Invalid RSA JWK.');
        }

        $sequence = self::derSequence(
            self::derInteger($modulus) .
            self::derInteger($exponent)
        );

        $bitString = "\x00" . $sequence;
        $publicKey = self::derSequence(
            self::derSequence(self::derObjectIdentifier('1.2.840.113549.1.1.1') . self::derNull()) .
            self::derBitString($bitString)
        );

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($publicKey), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    private static function derLength(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $bytes = '';

        while ($length > 0) {
            $bytes = chr($length & 0xff) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private static function derInteger(string $value): string
    {
        if (ord($value[0]) > 0x7f) {
            $value = "\x00" . $value;
        }

        return "\x02" . self::derLength(strlen($value)) . $value;
    }

    private static function derSequence(string $value): string
    {
        return "\x30" . self::derLength(strlen($value)) . $value;
    }

    private static function derBitString(string $value): string
    {
        return "\x03" . self::derLength(strlen($value)) . $value;
    }

    private static function derNull(): string
    {
        return "\x05\x00";
    }

    private static function derObjectIdentifier(string $oid): string
    {
        $parts = array_map('intval', explode('.', $oid));
        $value = chr(($parts[0] * 40) + $parts[1]);

        foreach (array_slice($parts, 2) as $part) {
            $encoded = chr($part & 0x7f);
            $part >>= 7;

            while ($part > 0) {
                $encoded = chr(0x80 | ($part & 0x7f)) . $encoded;
                $part >>= 7;
            }

            $value .= $encoded;
        }

        return "\x06" . self::derLength(strlen($value)) . $value;
    }
}
