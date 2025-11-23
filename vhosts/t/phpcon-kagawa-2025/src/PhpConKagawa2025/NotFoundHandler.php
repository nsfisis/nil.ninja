<?php

declare(strict_types=1);

namespace Nsfisis\TinyPhpHttpd\PhpConKagawa2025;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class NotFoundHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = '404 Not Found';

        return $this->responseFactory->createResponse(404)
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->withBody($this->streamFactory->createStream($body));
    }
}
