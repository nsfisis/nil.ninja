<?php

declare(strict_types=1);

namespace Nsfisis\TinyPhpHttpd\PhpConKagawa2025;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class CookieEatHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cookies = $request->getCookieParams();

        $orders = [];
        if (isset($cookies['order']) && $cookies['order'] !== '') {
            $orders = explode(',', $cookies['order']);
        }

        if (count($orders) > 0) {
            $orderList = '<ul>';
            foreach ($orders as $order) {
                $orderList .= '<li>' . htmlspecialchars($order) . '</li>';
            }
            $orderList .= '</ul>';
            $message = '<p>ごちそうさまでした。</p>';
        } else {
            $orderList = '';
            $message = '<p>注文がありません。</p>';
        }

        $body = <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>いただきます</title>
</head>
<body>
    <div class="container">
        <h1>いただきます</h1>
        {$orderList}
        {$message}
        <a href="/phpcon-kagawa-2025/cookie/">もう一度注文する</a>
    </div>
</body>
</html>
HTML;

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=UTF-8')
            ->withHeader('Set-Cookie', 'order=; Max-Age=0; path=/')
            ->withBody($this->streamFactory->createStream($body));
    }
}
