<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

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
        $response = $this->openaiClient->request('POST', 'embeddings', [
            'json' => [
                'model' => $this->embeddingModel,
                'input' => TextSanitizer::sanitizeUtf8($text),
            ],
        ]);

        $data = $response->toArray();

        if (!isset($data['data'][0]['embedding'])) {
            throw new \RuntimeException('Invalid response from OpenAI embeddings API');
        }

        $embedding = $data['data'][0]['embedding'];
        $dimensions = count($embedding);

        return [
            'embedding' => $embedding,
            'model' => $data['model'],
            'dimensions' => $dimensions,
            'tokens' => $data['usage']['total_tokens'] ?? 0,
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

        // OpenAI supports batch embedding requests
        $response = $this->openaiClient->request('POST', 'embeddings', [
            'json' => [
                'model' => $this->embeddingModel,
                'input' => array_map(TextSanitizer::sanitizeUtf8(...), $texts),
            ],
        ]);

        $data = $response->toArray();

        if (!isset($data['data']) || !is_array($data['data'])) {
            throw new \RuntimeException('Invalid response from OpenAI embeddings API');
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
