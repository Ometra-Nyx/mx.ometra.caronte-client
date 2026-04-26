<?php

namespace Ometra\Caronte\Exceptions;

use RuntimeException;

class CaronteApiException extends RuntimeException
{
    /**
     * @param  array<int|string, mixed>  $errors
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        string $message,
        int $status = 500,
        private readonly array $errors = [],
        private readonly array $payload = [],
    ) {
        parent::__construct($message, $status);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }
}
