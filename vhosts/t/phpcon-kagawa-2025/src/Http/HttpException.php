<?php

declare(strict_types=1);

namespace Nsfisis\TinyPhpHttpd\Http;

use RuntimeException;

/**
 * Thrown while reading a request to abort it with the given status code.
 */
final class HttpException extends RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
