<?php

declare(strict_types=1);

namespace Nsfisis\TinyPhpHttpd\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response implements ResponseInterface
{
    private int $statusCode;

    private string $reasonPhrase;

    private array $headers = [];

    private StreamInterface $body;

    private string $protocolVersion = '1.1';

    private static array $phrases = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    public function __construct(int $statusCode = 200, array $headers = [], string $body = '', string $reasonPhrase = '')
    {
        $this->statusCode = $statusCode;
        $this->reasonPhrase = $reasonPhrase !== '' ? $reasonPhrase : (self::$phrases[$statusCode] ?? '');
        $this->body = new Stream($body);

        foreach ($headers as $name => $value) {
            $this->headers[strtolower($name)] = [
                'name' => $name,
                'values' => is_array($value) ? $value : [$value],
            ];
        }
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;
        return $clone;
    }

    public function getHeaders(): array
    {
        $result = [];
        foreach ($this->headers as $header) {
            $result[$header['name']] = $header['values'];
        }
        return $result;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        $lower = strtolower($name);
        return $this->headers[$lower]['values'] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): static
    {
        $clone = clone $this;
        $clone->headers[strtolower($name)] = [
            'name' => $name,
            'values' => is_array($value) ? $value : [$value],
        ];
        return $clone;
    }

    public function withAddedHeader(string $name, $value): static
    {
        $clone = clone $this;
        $lower = strtolower($name);
        $values = is_array($value) ? $value : [$value];

        if (isset($clone->headers[$lower])) {
            $clone->headers[$lower]['values'] = array_merge($clone->headers[$lower]['values'], $values);
        } else {
            $clone->headers[$lower] = [
                'name' => $name,
                'values' => $values,
            ];
        }
        return $clone;
    }

    public function withoutHeader(string $name): static
    {
        $clone = clone $this;
        unset($clone->headers[strtolower($name)]);
        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase !== '' ? $reasonPhrase : (self::$phrases[$code] ?? '');
        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}
