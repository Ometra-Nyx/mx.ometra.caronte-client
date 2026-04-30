<?php

namespace Ometra\Caronte\Oidc;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class JwksCache
{
    public function key(string $kid): array
    {
        foreach ($this->jwks()['keys'] ?? [] as $key) {
            if (($key['kid'] ?? null) === $kid) {
                return $key;
            }
        }

        Cache::forget($this->cacheKey());

        foreach ($this->jwks()['keys'] ?? [] as $key) {
            if (($key['kid'] ?? null) === $kid) {
                return $key;
            }
        }

        throw new RuntimeException('Unknown OIDC signing key.');
    }

    private function jwks(): array
    {
        return Cache::remember($this->cacheKey(), (int) config('caronte.oidc.jwks_cache_ttl', 3600), function (): array {
            $issuer = rtrim((string) config('caronte.oidc.issuer'), '/');

            if ($issuer === '') {
                throw new RuntimeException('CARONTE_OIDC_ISSUER is not configured.');
            }

            $response = Http::acceptJson()
                ->withOptions(['verify' => (bool) config('caronte.tls_verify', true)])
                ->timeout((int) config('caronte.http.timeout', 10))
                ->get($issuer . '/oauth/jwks');

            if ($response->failed()) {
                throw new RuntimeException('Unable to fetch OIDC JWKS.');
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : ['keys' => []];
        });
    }

    private function cacheKey(): string
    {
        return 'caronte.oidc.jwks.' . sha1((string) config('caronte.oidc.issuer'));
    }
}
