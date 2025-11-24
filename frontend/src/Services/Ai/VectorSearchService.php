<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Terlicko\Web\Repository\AiEmbeddingRepository;

readonly final class VectorSearchService
{
    /**
     * Common topic expansions for short queries to improve search relevance
     * Maps common query terms to expanded search phrases
     *
     * @var array<string, string>
     */
    private const QUERY_EXPANSIONS = [
        'sport' => 'sport sportovní aktivity kluby tělovýchova',
        'fotbal' => 'fotbal fotbalový klub TJ hřiště',
        'hokej' => 'hokej hokejový klub zimní stadion',
        'kultura' => 'kultura kulturní akce události divadlo koncert',
        'škola' => 'škola základní mateřská vzdělávání',
        'úřad' => 'obecní úřad úřední hodiny kontakt',
        'starosta' => 'starosta vedení obce zastupitelstvo',
        'odpad' => 'odpad odpady svoz popelnice komunální',
        'voda' => 'voda vodné stočné vodovod kanalizace',
        'doprava' => 'doprava autobus MHD parkování silnice',
        'zdraví' => 'zdraví lékař ordinace zdravotnictví',
        'bydlení' => 'bydlení byty nemovitosti stavba',
        'příroda' => 'příroda přehrada les park turistika',
        'děti' => 'děti mládež kroužky volnočasové aktivity',
        'senioři' => 'senioři důchodci sociální služby',
    ];

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
    public function search(string $query, int $limit = 15): array
    {
        // Expand query for better search
        $expandedQuery = $this->expandQuery($query);

        // Generate embedding for the expanded query
        $embeddingData = $this->embeddingService->generateEmbedding($expandedQuery);

        // Search for similar chunks
        return $this->embeddingRepository->findSimilarChunks(
            $embeddingData['embedding'],
            $limit
        );
    }

    /**
     * Hybrid search combining vector similarity and keyword matching
     *
     * @param float $distanceThreshold Maximum cosine distance (0-2, lower = more similar). Results above this threshold are filtered out.
     * @param int $minResults Minimum number of results to return even if above threshold (for better UX)
     * @return array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, distance: float, keyword_rank: float, combined_score: float}>
     */
    public function hybridSearch(string $query, int $limit = 15, float $distanceThreshold = 0.65, int $minResults = 5): array
    {
        // Expand query for better search
        $expandedQuery = $this->expandQuery($query);

        // Generate embedding for the expanded query
        $embeddingData = $this->embeddingService->generateEmbedding($expandedQuery);

        // Perform hybrid search (pass original query for keyword matching)
        $results = $this->embeddingRepository->findSimilarChunksHybrid(
            $embeddingData['embedding'],
            $query, // Use original for keywords
            $limit
        );

        // Separate results by relevance threshold
        $relevant = [];
        $belowThreshold = [];

        foreach ($results as $result) {
            if ((float) $result['distance'] <= $distanceThreshold) {
                $relevant[] = $result;
            } else {
                $belowThreshold[] = $result;
            }
        }

        // Ensure minimum results: if we have fewer relevant results, include top below-threshold ones
        if (count($relevant) < $minResults && count($belowThreshold) > 0) {
            $needed = $minResults - count($relevant);
            $relevant = array_merge($relevant, array_slice($belowThreshold, 0, $needed));
        }

        return $relevant;
    }

    /**
     * Expand short or vague queries with contextual terms for better search
     *
     * Short queries like "sport" get expanded to include related terms,
     * improving both vector similarity matching and keyword search.
     */
    private function expandQuery(string $query): string
    {
        $normalizedQuery = mb_strtolower(trim($query));

        // Check for exact matches in expansion map
        if (isset(self::QUERY_EXPANSIONS[$normalizedQuery])) {
            return self::QUERY_EXPANSIONS[$normalizedQuery] . ' Těrlicko';
        }

        // Check for partial matches (query contains expansion key)
        foreach (self::QUERY_EXPANSIONS as $key => $expansion) {
            if (str_contains($normalizedQuery, $key)) {
                return $query . ' ' . $expansion . ' Těrlicko';
            }
        }

        // For short queries (1-2 words), add Těrlicko context
        $wordCount = str_word_count($normalizedQuery);
        if ($wordCount <= 2) {
            return $query . ' Těrlicko obec';
        }

        return $query;
    }
}
