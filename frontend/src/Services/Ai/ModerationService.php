<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly final class ModerationService
{
    public function __construct(
        private HttpClientInterface $openaiClient,
    ) {
    }

    /**
     * Check if text violates OpenAI's usage policies
     *
     * @return array{flagged: bool, categories: array<string, bool>}
     */
    public function moderateText(string $text): array
    {
        $response = $this->openaiClient->request('POST', 'moderations', [
            'json' => [
                'input' => $text,
            ],
        ]);

        /** @var array{results: array<array{flagged: bool, categories: array<string, bool>}>} $data */
        $data = $response->toArray();

        if (!isset($data['results'][0])) {
            throw new \RuntimeException('Invalid response from OpenAI moderation API');
        }

        $result = $data['results'][0];

        return [
            'flagged' => $result['flagged'],
            'categories' => $result['categories'],
        ];
    }

    /**
     * Check if text should be blocked
     */
    public function shouldBlock(string $text): bool
    {
        $result = $this->moderateText($text);
        return $result['flagged'];
    }
}
