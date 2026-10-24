<?php

declare(strict_types=1);

namespace Nsfisis\TinyPhpHttpd\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Throwable;

final readonly class Server implements ResponseFactoryInterface, StreamFactoryInterface
{
    // Maximum size of the request line and headers, including the terminating CRLF CRLF.
    private const int MAX_HEADER_SIZE = 8192;

    private const int MAX_BODY_SIZE = 65536;

    // Total time allowed to receive a whole request. Because this server handles one connection at a time,
    // this bounds how long a slow client (e.g. Slowloris) can block the others.
    private const float REQUEST_TIMEOUT_SEC = 5.0;

    private const int SEND_TIMEOUT_SEC = 5;

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

            // Never let a single connection take down the whole server.
            try {
                $this->handleConnection($sock, $handler);
            } catch (Throwable $e) {
                echo 'Error: ' . $e::class . ': ' . $e->getMessage() . "\n";
            } finally {
                socket_close($sock);
            }
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

    private function handleConnection(\Socket $sock, RequestHandlerInterface $handler): void
    {
        socket_set_option($sock, SOL_SOCKET, SO_SNDTIMEO, [
            'sec' => self::SEND_TIMEOUT_SEC,
            'usec' => 0,
        ]);

        try {
            echo 'Reading request...';
            $request = $this->readRequest($sock);
            echo "done\n";
            if ($request === null) {
                return;
            }

            echo 'Request: ' . $request->getMethod() . ' ' . $request->getRequestTarget() . "\n";

            try {
                $response = $handler->handle($request);
            } catch (Throwable $e) {
                echo 'Handler error: ' . $e::class . ': ' . $e->getMessage() . "\n";
                $response = $this->createErrorResponse(500);
            }
        } catch (HttpException $e) {
            echo "\nBad request: {$e->getStatusCode()} {$e->getMessage()}\n";
            $response = $this->createErrorResponse($e->getStatusCode());
        }

        // This server does not support persistent connections.
        $response = $response->withHeader('Connection', 'close');

        try {
            $responseString = $this->responseToString($response);
        } catch (Throwable $e) {
            echo 'Invalid response: ' . $e->getMessage() . "\n";
            $responseString = $this->responseToString($this->createErrorResponse(500)->withHeader('Connection', 'close'));
        }

        $this->writeAll($sock, $responseString);
    }

    private function createErrorResponse(int $statusCode): ResponseInterface
    {
        $response = $this->createResponse($statusCode);
        return $response
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->withBody($this->createStream("{$statusCode} {$response->getReasonPhrase()}"));
    }

    /**
     * Receives at most $maxLength bytes before the deadline.
     *
     * Returns null when the peer has closed the connection.
     */
    private function recvWithDeadline(\Socket $sock, int $maxLength, float $deadline): ?string
    {
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            throw new HttpException(408, 'request timeout');
        }
        $sec = (int) $remaining;
        socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => $sec,
            'usec' => max(1, (int) (($remaining - $sec) * 1_000_000)),
        ]);

        $chunk = '';
        $bytes = socket_recv($sock, $chunk, $maxLength, 0);
        if ($bytes === false) {
            $err = socket_last_error($sock);
            if ($err === SOCKET_EAGAIN) {
                throw new HttpException(408, 'request timeout');
            }
            return null;
        }
        if ($bytes === 0 || $chunk === null) {
            return null;
        }
        return $chunk;
    }

    private function readRequest(\Socket $sock): ?ServerRequest
    {
        $deadline = microtime(true) + self::REQUEST_TIMEOUT_SEC;

        // Read headers first
        $rawRequest = '';
        $headerEndPos = false;
        while ($headerEndPos === false) {
            if (strlen($rawRequest) >= self::MAX_HEADER_SIZE) {
                throw new HttpException(431, 'headers too large');
            }
            $chunk = $this->recvWithDeadline($sock, 8192, $deadline);
            if ($chunk === null) {
                if ($rawRequest === '') {
                    return null;
                }
                throw new HttpException(400, 'connection closed before headers were complete');
            }
            $rawRequest .= $chunk;
            $headerEndPos = strpos($rawRequest, "\r\n\r\n");
        }
        if ($headerEndPos + 4 > self::MAX_HEADER_SIZE) {
            throw new HttpException(431, 'headers too large');
        }

        [$method, $target, $headers] = $this->parseHead(substr($rawRequest, 0, $headerEndPos));
        $contentLength = $this->getContentLength($headers);

        // Read body based on Content-Length
        $body = substr($rawRequest, $headerEndPos + 4);
        while (strlen($body) < $contentLength) {
            $chunk = $this->recvWithDeadline($sock, min($contentLength - strlen($body), 8192), $deadline);
            if ($chunk === null) {
                throw new HttpException(400, 'connection closed before body was complete');
            }
            $body .= $chunk;
        }
        // Ignore anything sent after the body; pipelining is not supported.
        $body = substr($body, 0, $contentLength);

        $cookies = [];
        foreach ($headers['cookie'] ?? [] as $cookieHeader) {
            foreach (explode(';', $cookieHeader) as $pair) {
                $kv = explode('=', trim($pair), 2);
                if (count($kv) === 2) {
                    $cookies[$kv[0]] = urldecode($kv[1]);
                }
            }
        }

        return new ServerRequest(
            $method,
            $target,
            $headers,
            $body,
            [],
            $cookies,
        );
    }

    /**
     * Parses the request line and header fields strictly. Anything ambiguous is rejected, so that this server never
     * interprets a request differently from the reverse proxy in front of it (request smuggling).
     *
     * @return array{string, string, array<string, list<string>>}
     */
    private function parseHead(string $head): array
    {
        $lines = explode("\r\n", $head);

        $requestLine = array_shift($lines);
        // method SP origin-form SP HTTP-version
        if (preg_match('#\A([!\#$%&\'*+.^_`|~0-9A-Za-z-]+) (/[\x21-\x7E]*) HTTP/1\.[01]\z#', $requestLine, $m) !== 1) {
            throw new HttpException(400, 'malformed request line');
        }
        [, $method, $target] = $m;

        $headers = [];
        foreach ($lines as $line) {
            // field-name ":" OWS field-value OWS. Rejects obs-fold, whitespace before ":", bare CR/LF and NUL.
            if (preg_match('#\A([!\#$%&\'*+.^_`|~0-9A-Za-z-]+):[ \t]*([\x21-\x7E\x80-\xFF](?:[ \t\x21-\x7E\x80-\xFF]*[\x21-\x7E\x80-\xFF])?)?[ \t]*\z#', $line, $m) !== 1) {
                throw new HttpException(400, 'malformed header field');
            }
            $headers[strtolower($m[1])][] = $m[2] ?? '';
        }

        return [$method, $target, $headers];
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function getContentLength(array $headers): int
    {
        if (isset($headers['transfer-encoding'])) {
            throw new HttpException(501, 'Transfer-Encoding is not supported');
        }
        if (! isset($headers['content-length'])) {
            return 0;
        }
        if (count($headers['content-length']) !== 1 || preg_match('/\A[0-9]{1,10}\z/', $headers['content-length'][0]) !== 1) {
            throw new HttpException(400, 'invalid Content-Length');
        }
        $contentLength = (int) $headers['content-length'][0];
        if ($contentLength > self::MAX_BODY_SIZE) {
            throw new HttpException(413, 'body too large');
        }
        return $contentLength;
    }

    private function writeAll(\Socket $sock, string $data): void
    {
        while ($data !== '') {
            // MSG_NOSIGNAL: do not get killed by SIGPIPE when the peer has already gone away.
            $sent = socket_send($sock, $data, strlen($data), MSG_NOSIGNAL);
            if ($sent === false || $sent === 0) {
                echo 'socket_send() failed: ' . socket_strerror(socket_last_error($sock)) . "\n";
                return;
            }
            $data = substr($data, $sent);
        }
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

        $statusCode = $response->getStatusCode();
        if ($statusCode < 100 || $statusCode > 999) {
            throw new RuntimeException("Invalid status code: {$statusCode}");
        }
        $reasonPhrase = $response->getReasonPhrase();
        if (preg_match('/[^\t\x20-\x7E\x80-\xFF]/', $reasonPhrase) === 1) {
            throw new RuntimeException('Invalid reason phrase');
        }
        $result = sprintf(
            "HTTP/%s %d %s\r\n",
            $response->getProtocolVersion() === '1.0' ? '1.0' : '1.1',
            $statusCode,
            $reasonPhrase
        );

        foreach ($response->getHeaders() as $name => $values) {
            // Prevent header injection / response splitting.
            if (preg_match('/\A[!#$%&\'*+.^_`|~0-9A-Za-z-]+\z/', (string) $name) !== 1) {
                throw new RuntimeException('Invalid header name');
            }
            foreach ($values as $value) {
                if (preg_match('/[^\t\x20-\x7E\x80-\xFF]/', $value) === 1) {
                    throw new RuntimeException("Invalid value for header {$name}");
                }
                $result .= "{$name}: {$value}\r\n";
            }
        }

        $result .= "\r\n";
        $result .= (string) $response->getBody();

        return $result;
    }
}
