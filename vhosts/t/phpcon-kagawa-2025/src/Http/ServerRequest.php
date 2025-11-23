<?php

declare(strict_types=1);

namespace Nsfisis\TinyPhpHttpd\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

final class ServerRequest implements ServerRequestInterface
{
    private string $method;

    private string $requestTarget;

    private array $headers;

    private StreamInterface $body;

    private string $protocolVersion = '1.1';

    private array $serverParams;

    private array $cookieParams;

    private array $queryParams;

    private array $uploadedFiles = [];

    private array|object|null $parsedBody = null;

    private array $attributes = [];

    public function __construct(
        string $method,
        string $requestTarget,
        array $headers = [],
        string $body = '',
        array $serverParams = [],
        array $cookieParams = [],
        array $queryParams = []
    ) {
        $this->method = $method;
        $this->requestTarget = $requestTarget;
        $this->body = new Stream($body);
        $this->serverParams = $serverParams;
        $this->cookieParams = $cookieParams;
        $this->queryParams = $queryParams;

        $this->headers = [];
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

    public function getRequestTarget(): string
    {
        return $this->requestTarget;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;
        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        $clone = clone $this;
        $clone->method = $method;
        return $clone;
    }

    public function getUri(): UriInterface
    {
        throw new RuntimeException('Not implemented');
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        throw new RuntimeException('Not implemented');
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;
        return $clone;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): static
    {
        $clone = clone $this;
        $clone->queryParams = $query;
        return $clone;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): static
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;
        return $clone;
    }

    public function getParsedBody(): array|object|null
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data): static
    {
        $clone = clone $this;
        $clone->parsedBody = $data;
        return $clone;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }
}
