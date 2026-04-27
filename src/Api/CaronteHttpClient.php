<?php

namespace Ometra\Caronte\Api;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Ometra\Caronte\Exceptions\CaronteApiException;
use Ometra\Caronte\Support\ApplicationToken;

class CaronteHttpClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public function authRequest(
        string $method,
        string $endpoint,
        array $payload = [],
        array $query = [],
        ?string $userToken = null,
    ): array {
        $headers = [
            'X-Application-Token' => ApplicationToken::make(),
        ];

        if (is_string($userToken) && $userToken !== '') {
            $headers['X-User-Token'] = $userToken;
        }

        return $this->request($method, $endpoint, $payload, $query, $headers);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    public function applicationRequest(
        string $method,
        string $endpoint,
        array $payload = [],
        array $query = [],
        ?string $tenantId = null,
    ): array {
        $headers = [
            'X-Application-Token' => ApplicationToken::make(),
        ];

        if (is_string($tenantId) && trim($tenantId) !== '') {
            $headers['X-Tenant-Id'] = trim($tenantId);
        }

        return $this->request($method, $endpoint, $payload, $query, $headers);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     * @param  array<string, string>  $headers
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    private function request(
        string $method,
        string $endpoint,
        array $payload,
        array $query,
        array $headers,
    ): array {
        $request = $this->baseRequest()->withHeaders($headers);
        $url = rtrim((string) config('caronte.URL'), '/') . '/' . ltrim($endpoint, '/');
        $method = strtolower($method);

        $response = match ($method) {
            'get' => $request->get($url, $query),
            'delete' => $request->delete($url, $payload !== [] ? $payload : $query),
            'post' => $request->post($this->appendQuery($url, $query), $payload),
            'put' => $request->put($this->appendQuery($url, $query), $payload),
            'patch' => $request->patch($this->appendQuery($url, $query), $payload),
            default => throw new CaronteApiException("Unsupported HTTP method [{$method}].", 500),
        };

        return $this->parseResponse($response);
    }

    private function baseRequest(): PendingRequest
    {
        return Http::acceptJson()
            ->withOptions([
                'verify' => (bool) config('caronte.TLS_VERIFY', true),
            ])
            ->timeout((int) config('caronte.HTTP.timeout', 10))
            ->retry(
                times: (int) config('caronte.HTTP.retries', 1),
                sleepMilliseconds: (int) config('caronte.HTTP.retry_sleep', 150)
            );
    }

    /**
     * @return array{status: int, message: string, data: mixed, errors: array<int|string, mixed>}
     */
    private function parseResponse(Response $response): array
    {
        $payload = $response->json();

        if (!is_array($payload)) {
            $payload = [
                'status' => $response->status(),
                'message' => $response->successful() ? 'Request completed successfully.' : 'Unexpected response from Caronte.',
                'data' => null,
                'errors' => [],
            ];
        }

        $status = (int) ($payload['status'] ?? $response->status());
        $message = (string) ($payload['message'] ?? $response->reason());
        $errors = is_array($payload['errors'] ?? null) ? $payload['errors'] : [];

        if ($response->failed()) {
            throw new CaronteApiException(
                message: $message !== '' ? $message : 'Caronte request failed.',
                status: $status > 0 ? $status : $response->status(),
                errors: $errors,
                payload: $payload,
            );
        }

        return [
            'status' => $status > 0 ? $status : $response->status(),
            'message' => $message !== '' ? $message : 'Request completed successfully.',
            'data' => $payload['data'] ?? null,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function appendQuery(string $url, array $query): string
    {
        if ($query === []) {
            return $url;
        }

        return $url . '?' . http_build_query($query);
    }
}
