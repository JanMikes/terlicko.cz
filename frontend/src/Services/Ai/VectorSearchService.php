<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Terlicko\Web\Repository\AiEmbeddingRepository;

readonly final class VectorSearchService
{
    public function __construct(
        private AiEmbeddingRepository $embeddingRepository,
        private EmbeddingService $embeddingService,
    ) {
    }

    /**
     * Search for relevant chunks using vector similarity
     *
     * @return array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, distance: float}>
     */
    public function search(string $query, int $limit = 10): array
    {
        // Generate embedding for the query
        $embeddingData = $this->embeddingService->generateEmbedding($query);

        // Search for similar chunks
        return $this->embeddingRepository->findSimilarChunks(
            $embeddingData['embedding'],
            $limit
        );
    }

    /**
     * Hybrid search combining vector similarity and keyword matching
     *
     * @return array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, distance: float, keyword_rank: float, combined_score: float}>
     */
    public function hybridSearch(string $query, int $limit = 10): array
    {
        // Generate embedding for the query
        $embeddingData = $this->embeddingService->generateEmbedding($query);

        // Perform hybrid search
        return $this->embeddingRepository->findSimilarChunksHybrid(
            $embeddingData['embedding'],
            $query,
            $limit
        );
    }
}
