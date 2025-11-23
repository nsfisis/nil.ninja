<?php

declare(strict_types=1);

namespace Nsfisis\TinyPhpHttpd;

use Nsfisis\TinyPhpHttpd\Http\Server;
use Nsfisis\TinyPhpHttpd\PhpConKagawa2025\CookieEatHandler;
use Nsfisis\TinyPhpHttpd\PhpConKagawa2025\CookieHandler;
use Nsfisis\TinyPhpHttpd\PhpConKagawa2025\GetHandler;
use Nsfisis\TinyPhpHttpd\PhpConKagawa2025\HealthHandler;
use Nsfisis\TinyPhpHttpd\PhpConKagawa2025\NotFoundHandler;
use Nsfisis\TinyPhpHttpd\PhpConKagawa2025\PostHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class App implements RequestHandlerInterface
{
    private Server $server;

    /**
     * @var array<string, RequestHandlerInterface>
     */
    private array $routes = [];

    private RequestHandlerInterface $notFoundHandler;

    public function __construct(string $host, int $port)
    {
        $this->server = new Server($host, $port);
        $this->notFoundHandler = new NotFoundHandler($this->server, $this->server);
    }

    public function run(): void
    {
        $this->addRoute('/phpcon-kagawa-2025/health/', new HealthHandler($this->server, $this->server));
        $this->addRoute('/phpcon-kagawa-2025/get/', new GetHandler($this->server, $this->server));
        $this->addRoute('/phpcon-kagawa-2025/post/', new PostHandler($this->server, $this->server));
        $this->addRoute('/phpcon-kagawa-2025/cookie/', new CookieHandler($this->server, $this->server));
        $this->addRoute('/phpcon-kagawa-2025/cookie/eat/', new CookieEatHandler($this->server, $this->server));

        $this->server->run($this);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getRequestTarget();
        if ($path !== '/' && ! str_ends_with($path, '/')) {
            $path .= '/';
        }
        $handler = $this->routes[$path] ?? $this->notFoundHandler;
        return $handler->handle($request);
    }

    private function addRoute(string $path, RequestHandlerInterface $handler): self
    {
        $this->routes[$path] = $handler;
        return $this;
    }
}
