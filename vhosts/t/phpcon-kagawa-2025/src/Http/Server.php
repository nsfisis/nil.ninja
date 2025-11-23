<?php

declare(strict_types=1);

namespace Nsfisis\TinyPhpHttpd\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final readonly class Server implements ResponseFactoryInterface, StreamFactoryInterface
{
    public function __construct(
        private string $host,
        private int $port
    ) {
    }

    public function run(RequestHandlerInterface $handler): void
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            throw new RuntimeException('socket_create() failed: ' . socket_strerror(socket_last_error()));
        }

        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

        if (socket_bind($socket, $this->host, $this->port) === false) {
            throw new RuntimeException('socket_bind() failed: ' . socket_strerror(socket_last_error($socket)));
        }

        if (socket_listen($socket, 5) === false) {
            throw new RuntimeException('socket_listen() failed: ' . socket_strerror(socket_last_error($socket)));
        }

        echo "HTTP server started on http://{$this->host}:{$this->port}\n";
        echo "Press Ctrl+C to stop\n\n";

        for (; ;) {
            $sock = socket_accept($socket);
            if ($sock === false) {
                echo 'socket_accept() failed: ' . socket_strerror(socket_last_error($socket)) . "\n";
                continue;
            }

            echo 'Reading request...';
            $rawRequest = $this->readRequest($sock);
            echo "done\n";

            if ($rawRequest !== '') {
                $request = $this->parseRequest($rawRequest);

                echo 'Request: ' . $request->getMethod() . ' ' . $request->getRequestTarget() . "\n";

                $response = $handler->handle($request);

                if (! $response->hasHeader('Connection')) {
                    $response = $response->withHeader('Connection', 'close');
                }

                $responseString = $this->responseToString($response);
                socket_write($sock, $responseString, strlen($responseString));
            }

            socket_close($sock);
        }

        // @phpstan-ignore deadCode.unreachable
        socket_close($socket);
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, [], '', $reasonPhrase);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        throw new RuntimeException('Not implemented');
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * @param \Socket $sock
     */
    private function readRequest($sock): string
    {
        // Set socket timeout (5 seconds)
        socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => 5,
            'usec' => 0,
        ]);

        $rawRequest = '';
        $headersComplete = false;
        $contentLength = 0;
        $headerEndPos = 0;

        // Read headers first
        for (; ;) {
            $chunk = '';
            $bytes = socket_recv($sock, $chunk, 8192, 0);
            if ($bytes === false || $bytes === 0 || $chunk === null) {
                break;
            }
            $rawRequest .= $chunk;

            // Check if headers are complete
            $headerEndPos = strpos($rawRequest, "\r\n\r\n");
            if ($headerEndPos !== false) {
                $headersComplete = true;
                $headerEndPos += 4; // Include \r\n\r\n

                // Parse Content-Length from headers
                $headerSection = substr($rawRequest, 0, $headerEndPos);
                if (preg_match('/^Content-Length:\s*(\d+)/mi', $headerSection, $matches)) {
                    $contentLength = (int) $matches[1];
                }
                break;
            }
        }

        // Read body based on Content-Length
        if ($headersComplete && $contentLength > 0) {
            $bodyReceived = strlen($rawRequest) - $headerEndPos;
            while ($bodyReceived < $contentLength) {
                $chunk = '';
                $remaining = $contentLength - $bodyReceived;
                $bytes = socket_recv($sock, $chunk, min($remaining, 8192), 0);
                if ($bytes === false || $bytes === 0 || $chunk === null) {
                    break;
                }
                $rawRequest .= $chunk;
                $bodyReceived += $bytes;
            }
        }

        return $rawRequest;
    }

    private function parseRequest(string $rawRequest): ServerRequest
    {
        $lines = explode("\r\n", $rawRequest);
        $requestLine = trim($lines[0]);

        $parts = explode(' ', $requestLine);
        $method = $parts[0];
        $path = $parts[1] ?? '/';

        $headers = [];
        $bodyStart = 0;
        for ($i = 1; $i < count($lines); $i++) {
            if ($lines[$i] === '') {
                $bodyStart = $i + 1;
                break;
            }
            $headerParts = explode(':', $lines[$i], 2);
            if (count($headerParts) === 2) {
                $headers[strtolower(trim($headerParts[0]))] = trim($headerParts[1]);
            }
        }

        $requestBody = '';
        if ($bodyStart > 0 && $bodyStart < count($lines)) {
            $requestBody = implode("\r\n", array_slice($lines, $bodyStart));
        }

        $cookies = [];
        if (isset($headers['cookie'])) {
            $cookiePairs = explode(';', $headers['cookie']);
            foreach ($cookiePairs as $pair) {
                $kv = explode('=', trim($pair), 2);
                if (count($kv) === 2) {
                    $cookies[$kv[0]] = urldecode($kv[1]);
                }
            }
        }

        return new ServerRequest(
            $method,
            $path,
            $headers,
            $requestBody,
            [],
            $cookies,
        );
    }

    private function responseToString(ResponseInterface $response): string
    {
        if (! $response->hasHeader('Content-Length')) {
            $size = $response->getBody()->getSize();
            if ($size === null) {
                throw new RuntimeException('Cannot determine body size for Content-Length header');
            }
            $response = $response->withHeader('Content-Length', (string) $size);
        }

        $result = sprintf(
            "HTTP/%s %d %s\r\n",
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $result .= "{$name}: {$value}\r\n";
            }
        }

        $result .= "\r\n";
        $result .= (string) $response->getBody();

        return $result;
    }
}
