<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly final class EmbeddingService
{
    public function __construct(
        private HttpClientInterface $openaiClient,
        private string $embeddingModel,
    ) {
    }

    /**
     * Generate embedding for text
     *
     * @return array{embedding: array<float>, model: string, dimensions: int, tokens: int}
     */
    public function generateEmbedding(string $text): array
    {
        $sanitizedText = TextSanitizer::sanitizeUtf8($text);

        try {
            $response = $this->openaiClient->request('POST', 'embeddings', [
                'json' => [
                    'model' => $this->embeddingModel,
                    'input' => $sanitizedText,
                ],
            ]);

            /** @var array{data: array<array{embedding: array<float>}>, model: string, usage: array{total_tokens: int}} $data */
            $data = $response->toArray();
        } catch (HttpExceptionInterface $e) {
            // Extract the actual error response from OpenAI for better debugging
            $errorBody = '';
            try {
                $errorBody = $e->getResponse()->getContent(false);
            } catch (\Throwable) {
                // Ignore errors when reading error response
            }

            $textLength = mb_strlen($sanitizedText);
            throw new \RuntimeException(sprintf(
                'OpenAI embeddings API error (HTTP %d): %s. Text length: %d chars. Response: %s',
                $e->getResponse()->getStatusCode(),
                $e->getMessage(),
                $textLength,
                $errorBody ?: 'N/A'
            ), 0, $e);
        }

        if (!isset($data['data'][0]['embedding'])) {
            throw new \RuntimeException('Invalid response from OpenAI embeddings API');
        }

        $embedding = $data['data'][0]['embedding'];
        $dimensions = count($embedding);

        return [
            'embedding' => $embedding,
            'model' => $data['model'],
            'dimensions' => $dimensions,
            'tokens' => $data['usage']['total_tokens'],
        ];
    }

    /**
     * Generate embeddings for multiple texts in batch
     *
     * @param array<string> $texts
     * @return array<array{embedding: array<float>, model: string, dimensions: int}>
     */
    public function generateEmbeddings(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $sanitizedTexts = array_map(TextSanitizer::sanitizeUtf8(...), $texts);

        try {
            // OpenAI supports batch embedding requests
            $response = $this->openaiClient->request('POST', 'embeddings', [
                'json' => [
                    'model' => $this->embeddingModel,
                    'input' => $sanitizedTexts,
                ],
            ]);

            /** @var array{data: array<array{embedding: array<float>}>, model: string} $data */
            $data = $response->toArray();
        } catch (HttpExceptionInterface $e) {
            // Extract the actual error response from OpenAI for better debugging
            $errorBody = '';
            try {
                $errorBody = $e->getResponse()->getContent(false);
            } catch (\Throwable) {
                // Ignore errors when reading error response
            }

            $totalChars = array_sum(array_map('mb_strlen', $sanitizedTexts));
            throw new \RuntimeException(sprintf(
                'OpenAI embeddings API error (HTTP %d): %s. Batch size: %d texts, total %d chars. Response: %s',
                $e->getResponse()->getStatusCode(),
                $e->getMessage(),
                count($sanitizedTexts),
                $totalChars,
                $errorBody ?: 'N/A'
            ), 0, $e);
        }

        $results = [];
        foreach ($data['data'] as $item) {
            $embedding = $item['embedding'];
            $results[] = [
                'embedding' => $embedding,
                'model' => $data['model'],
                'dimensions' => count($embedding),
            ];
        }

        return $results;
    }

    /**
     * Get the embedding model name
     */
    public function getModelName(): string
    {
        return $this->embeddingModel;
    }

    /**
     * Get expected dimensions for the model
     */
    public function getDimensions(): int
    {
        // text-embedding-3-small: 1536 dimensions
        // text-embedding-3-large: 3072 dimensions
        return match ($this->embeddingModel) {
            'text-embedding-3-small' => 1536,
            'text-embedding-3-large' => 3072,
            'text-embedding-ada-002' => 1536,
            default => 1536,
        };
    }
}
