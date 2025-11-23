<?php

declare(strict_types=1);

namespace Nsfisis\TinyPhpHttpd\PhpConKagawa2025;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class PostHandler implements RequestHandlerInterface
{
    /**
     * @var array<string, string>
     */
    private const array ANSWERS = [
        'ehime' => 'hiroshima',
        'kagawa' => 'okayama',
        'tokushima' => 'hyogo',
    ];

    /**
     * @var array<string, string>
     */
    private const array PREFECTURE_NAMES = [
        'okayama' => '岡山',
        'hiroshima' => '広島',
        'yamaguchi' => '山口',
        'hyogo' => '兵庫',
        'osaka' => '大阪',
    ];

    private const string QUIZ_FORM = <<<'HTML'
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>本州四国連絡橋クイズ</title>
</head>
<body>
    <main>
        <h1>本州四国連絡橋クイズ</h1>
        <p>四国の各県と橋でつながっている本州の県を選んでください。</p>
        <form method="POST" action="/phpcon-kagawa-2025/post/">
            <div>
                <label>愛媛とつながっているのは？</label>
                <select name="ehime">
                    <option value="">選択してください</option>
                    <option value="okayama">岡山</option>
                    <option value="hiroshima">広島</option>
                    <option value="yamaguchi">山口</option>
                    <option value="hyogo">兵庫</option>
                    <option value="osaka">大阪</option>
                </select>
            </div>
            <div>
                <label>香川とつながっているのは？</label>
                <select name="kagawa">
                    <option value="">選択してください</option>
                    <option value="okayama">岡山</option>
                    <option value="hiroshima">広島</option>
                    <option value="yamaguchi">山口</option>
                    <option value="hyogo">兵庫</option>
                    <option value="osaka">大阪</option>
                </select>
            </div>
            <div>
                <label>徳島とつながっているのは？</label>
                <select name="tokushima">
                    <option value="">選択してください</option>
                    <option value="okayama">岡山</option>
                    <option value="hiroshima">広島</option>
                    <option value="yamaguchi">山口</option>
                    <option value="hyogo">兵庫</option>
                    <option value="osaka">大阪</option>
                </select>
            </div>
            <button type="submit">回答する</button>
        </form>
    </main>
</body>
</html>
HTML;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $postData = [];
            parse_str((string) $request->getBody(), $postData);

            $userEhime = $postData['ehime'] ?? '';
            $userKagawa = $postData['kagawa'] ?? '';
            $userTokushima = $postData['tokushima'] ?? '';

            $results = [];
            $score = 0;

            // Ehime
            $userEhimeName = self::PREFECTURE_NAMES[$userEhime] ?? $userEhime;
            if ($userEhime === self::ANSWERS['ehime']) {
                $results[] = '<p>愛媛: ' . htmlspecialchars($userEhimeName) . ' ⭕ 正解！</p>';
                $score++;
            } else {
                $results[] = '<p>愛媛: ' . htmlspecialchars($userEhimeName) . ' ❌ 不正解</p>';
            }

            // Kagawa
            $userKagawaName = self::PREFECTURE_NAMES[$userKagawa] ?? $userKagawa;
            if ($userKagawa === self::ANSWERS['kagawa']) {
                $results[] = '<p>香川: ' . htmlspecialchars($userKagawaName) . ' ⭕ 正解！</p>';
                $score++;
            } else {
                $results[] = '<p>香川: ' . htmlspecialchars($userKagawaName) . ' ❌ 不正解</p>';
            }

            // Tokushima
            $userTokushimaName = self::PREFECTURE_NAMES[$userTokushima] ?? $userTokushima;
            if ($userTokushima === self::ANSWERS['tokushima']) {
                $results[] = '<p>徳島: ' . htmlspecialchars($userTokushimaName) . ' ⭕ 正解！</p>';
                $score++;
            } else {
                $results[] = '<p>徳島: ' . htmlspecialchars($userTokushimaName) . ' ❌ 不正解</p>';
            }

            $resultHtml = implode("\n", $results);

            $body = <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>クイズ結果</title>
</head>
<body>
    <main>
        <h1>クイズ結果</h1>
        <p class="score">スコア: {$score} / 3</p>
        {$resultHtml}
        <p>
            <a href="/phpcon-kagawa-2025/post/">もう一度挑戦する</a>
        </p>
    </main>
</body>
</html>
HTML;
        } else {
            $body = self::QUIZ_FORM;
        }

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=UTF-8')
            ->withBody($this->streamFactory->createStream($body));
    }
}
