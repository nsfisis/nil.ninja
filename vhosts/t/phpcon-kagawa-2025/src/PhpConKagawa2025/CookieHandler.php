<?php

declare(strict_types=1);

namespace Nsfisis\TinyPhpHttpd\PhpConKagawa2025;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class CookieHandler implements RequestHandlerInterface
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

        $setCookie = null;

        if ($request->getMethod() === 'POST') {
            $postData = [];
            parse_str((string) $request->getBody(), $postData);
            $newOrder = $postData['udon'] ?? '';
            if ($newOrder !== '') {
                $orders[] = $newOrder;
                $setCookie = 'order=' . urlencode(implode(',', $orders)) . '; path=/';
            }
        }

        $orderList = '';
        if (count($orders) > 0) {
            $orderList = '<h2>現在の注文</h2><ul>';
            foreach ($orders as $order) {
                $orderList .= '<li>' . htmlspecialchars($order) . '</li>';
            }
            $orderList .= '</ul>';
            $orderList .= '<a href="/phpcon-kagawa-2025/cookie/eat/">食べる</a>';
        } else {
            $orderList = '<p>まだ注文がありません。</p>';
        }

        $body = <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>うどん店</title>
</head>
<body>
    <main>
        <h1>うどん店</h1>
        <form method="POST" action="/cookie/">
            <div>
                <select name="udon">
                    <option value="かけ">かけ</option>
                    <option value="ぶっかけ">ぶっかけ</option>
                    <option value="釜揚げ">釜揚げ</option>
                    <option value="釜玉">釜玉</option>
                </select>
            </div>
            <button type="submit">注文</button>
        </form>
        {$orderList}
    </main>
</body>
</html>
HTML;

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=UTF-8')
            ->withBody($this->streamFactory->createStream($body));

        if ($setCookie !== null) {
            $response = $response->withHeader('Set-Cookie', $setCookie);
        }

        return $response;
    }
}
