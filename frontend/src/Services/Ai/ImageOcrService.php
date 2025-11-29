<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly final class ImageOcrService
{
    public function __construct(
        private HttpClientInterface $openaiClient,
        private string $visionModel = 'gpt-4o',
    ) {
    }

    private const MAX_RETRIES = 3;
    private const INITIAL_DELAY_MS = 2000; // 2 seconds

    /**
     * Extract text from image using OpenAI Vision API
     *
     * @return array{text: string, model: string, tokens: int}
     */
    public function extractText(string $imageUrl): array
    {
        $lastException = null;

        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            try {
                $response = $this->openaiClient->request('POST', 'chat/completions', [
                    'json' => [
                        'model' => $this->visionModel,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'Extract all text from this image. Return only the extracted text exactly as it appears, preserving the original formatting and line breaks. If there is no text in the image, return an empty string.',
                                    ],
                                    [
                                        'type' => 'image_url',
                                        'image_url' => [
                                            'url' => $imageUrl,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'max_tokens' => 4096,
                    ],
                ]);

                /** @var array{choices: array<array{message: array{content: string}}>, model: string, usage: array{total_tokens: int}} $data */
                $data = $response->toArray();

                if (!isset($data['choices'][0]['message']['content'])) {
                    throw new \RuntimeException('Invalid response from OpenAI Vision API');
                }

                $extractedText = $data['choices'][0]['message']['content'];

                return [
                    'text' => TextSanitizer::sanitizeUtf8($extractedText),
                    'model' => $data['model'],
                    'tokens' => $data['usage']['total_tokens'],
                ];
            } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
                $lastException = $e;
                $statusCode = $e->getResponse()->getStatusCode();

                // Retry on rate limit (429) or server errors (5xx)
                if ($statusCode === 429 || $statusCode >= 500) {
                    $delayMs = self::INITIAL_DELAY_MS * (2 ** $attempt); // Exponential backoff
                    usleep($delayMs * 1000);
                    continue;
                }

                throw $e;
            }
        }

        throw $lastException ?? new \RuntimeException('Failed to extract text from image after retries');
    }
}
