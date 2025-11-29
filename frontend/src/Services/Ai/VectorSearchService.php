<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Terlicko\Web\Repository\AiEmbeddingRepository;

readonly final class VectorSearchService
{
    /**
     * Czech question words to strip from queries before embedding generation
     * These words don't add semantic meaning and dilute the embedding quality
     *
     * @var array<string>
     */
    private const CZECH_QUESTION_WORDS = [
        // Question pronouns - jaký
        'jaký', 'jaká', 'jaké', 'jakou', 'jakým', 'jakých', 'jakými', 'jakého', 'jakému',
        // Question pronouns - který
        'který', 'která', 'které', 'kterou', 'kterým', 'kterých', 'kterými', 'kterého', 'kterému',
        // Quantity
        'kolik', 'kolikrát', 'kolikátý', 'kolikátá',
        // Place/direction
        'kdy', 'kde', 'kam', 'odkud', 'kudy', 'odkdy', 'dokdy',
        // Manner/reason
        'jak', 'proč', 'nač', 'zač', 'začo',
        // What
        'co', 'čeho', 'čemu', 'čím', 'čem',
        // Who
        'kdo', 'koho', 'komu', 'kým', 'kom',
        // Whose
        'čí', 'čího', 'čímu', 'čím', 'číž',
    ];

    /**
     * Common Czech verbs that don't add meaning to search queries
     *
     * @var array<string>
     */
    private const MEANINGLESS_VERBS = [
        // To be
        'je', 'jsou', 'jsem', 'jste', 'jsme', 'byl', 'byla', 'bylo', 'byli', 'byly', 'bude', 'budou',
        // To have
        'má', 'mají', 'máte', 'máme', 'mám', 'měl', 'měla', 'měli', 'mělo',
        // Finding/searching
        'najdu', 'najít', 'hledat', 'hledám', 'zjistit', 'zjistím', 'dozvědět', 'dozvím',
        // Can/may
        'můžu', 'mohu', 'můžete', 'můžeme', 'lze', 'dá', 'dají',
        // Commands
        'řekni', 'pověz', 'ukaž', 'poraď', 'napiš', 'vysvětli',
        // Misc
        'chci', 'chceme', 'potřebuji', 'potřebujeme', 'chtěl', 'chtěla',
    ];

    /**
     * Common topic expansions for short queries to improve search relevance
     * Maps common query terms to expanded search phrases
     *
     * @var array<string, string>
     */
    private const QUERY_EXPANSIONS = [
        // Sports
        'sport' => 'sport sportovní aktivity kluby tělovýchova',
        'fotbal' => 'fotbal fotbalový klub TJ hřiště',
        'hokej' => 'hokej hokejový klub zimní stadion',
        // Culture & Education
        'kultura' => 'kultura kulturní akce události divadlo koncert',
        'škola' => 'škola základní mateřská vzdělávání',
        // Administration
        'úřad' => 'obecní úřad úřední hodiny kontakt',
        'starosta' => 'starosta vedení obce zastupitelstvo',
        'kontakt' => 'kontakt telefon email adresa úřad',
        'poplatky' => 'poplatky platby ceny sazby místní',
        // Utilities
        'odpad' => 'odpad odpady svoz popelnice komunální',
        'voda' => 'voda vodné stočné vodovod kanalizace',
        'doprava' => 'doprava autobus MHD parkování silnice',
        // Health & Social
        'zdraví' => 'zdraví lékař ordinace zdravotnictví',
        'senioři' => 'senioři důchodci sociální služby',
        // Housing & Nature
        'bydlení' => 'bydlení byty nemovitosti stavba',
        'příroda' => 'příroda přehrada les park turistika',
        // Children
        'děti' => 'děti mládež kroužky volnočasové aktivity',
        // Municipality info - NEW
        'rozloha' => 'rozloha velikost plocha rozměr km² kilometrů čtverečních výměra',
        'počet' => 'počet obyvatel občanů lidí populace',
        'historie' => 'historie dějiny založení vznik minulost letopočet',
        'symboly' => 'symboly znak vlajka erb heraldika',
        'erb' => 'erb znak symboly heraldika',
        'znak' => 'znak erb symboly heraldika',
        'mapa' => 'mapa poloha umístění lokace zeměpisný',
        'adresa' => 'adresa sídlo úřad kontakt',
    ];

    public function __construct(
        private AiEmbeddingRepository $embeddingRepository,
        private EmbeddingService $embeddingService,
        private QueryNormalizerService $queryNormalizer,
    ) {
    }

    /**
     * Search for relevant chunks using vector similarity
     *
     * @return array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, distance: float}>
     */
    public function search(string $query, int $limit = 15): array
    {
        // Normalize query using LLM (handles Czech declension)
        $normalizedQuery = $this->queryNormalizer->normalizeQuery($query);

        // Preprocess query to remove question words and meaningless verbs
        $preprocessedQuery = $this->preprocessQueryForEmbedding($normalizedQuery);

        // Expand query for better search
        $expandedQuery = $this->expandQuery($preprocessedQuery);

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
     * @return array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, document_type: string, distance: float, keyword_rank: float, combined_score: float}>
     */
    public function hybridSearch(string $query, int $limit = 15, float $distanceThreshold = 0.65, int $minResults = 5): array
    {
        // Normalize query using LLM (handles Czech declension)
        $normalizedQuery = $this->queryNormalizer->normalizeQuery($query);

        // Preprocess query to remove question words and meaningless verbs
        $preprocessedQuery = $this->preprocessQueryForEmbedding($normalizedQuery);

        // Expand query for better search
        $expandedQuery = $this->expandQuery($preprocessedQuery);

        // Generate embedding for the expanded query
        $embeddingData = $this->embeddingService->generateEmbedding($expandedQuery);

        // Perform hybrid search (pass preprocessed query for keyword matching, not original)
        $results = $this->embeddingRepository->findSimilarChunksHybrid(
            $embeddingData['embedding'],
            $preprocessedQuery, // Use preprocessed for keywords
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
     * Dual-query hybrid search combining original and LLM-normalized queries
     *
     * This method runs searches with both the original query and the LLM-normalized query,
     * then merges results using Reciprocal Rank Fusion (RRF). This preserves the benefits
     * of normalization (Czech grammar handling, synonyms) while maintaining precision
     * of the original query.
     *
     * @param float $distanceThreshold Maximum cosine distance (0-2, lower = more similar)
     * @param int $minResults Minimum number of results to return even if above threshold
     * @return array<array<string, mixed>>
     */
    public function dualQueryHybridSearch(
        string $query,
        int $limit = 15,
        float $distanceThreshold = 0.65,
        int $minResults = 5
    ): array {
        // 1. Search with ORIGINAL query (preprocessed but NOT LLM-normalized)
        $preprocessedOriginal = $this->preprocessQueryForEmbedding($query);
        $expandedOriginal = $this->expandQuery($preprocessedOriginal);
        $embeddingOriginal = $this->embeddingService->generateEmbedding($expandedOriginal);

        // 2. Search with NORMALIZED query (LLM-normalized)
        $normalizedQuery = $this->queryNormalizer->normalizeQuery($query);
        $preprocessedNormalized = $this->preprocessQueryForEmbedding($normalizedQuery);
        $expandedNormalized = $this->expandQuery($preprocessedNormalized);
        $embeddingNormalized = $this->embeddingService->generateEmbedding($expandedNormalized);

        // 3. Get results from both pipelines (get more to ensure overlap)
        $resultsOriginal = $this->embeddingRepository->findSimilarChunksHybrid(
            $embeddingOriginal['embedding'],
            $preprocessedOriginal,
            $limit * 2
        );

        $resultsNormalized = $this->embeddingRepository->findSimilarChunksHybrid(
            $embeddingNormalized['embedding'],
            $preprocessedNormalized,
            $limit * 2
        );

        // 4. Merge with RRF
        $merged = $this->mergeWithRRF($resultsOriginal, $resultsNormalized);

        // 5. Check for webpage presence and apply fallback if needed
        $hasWebpage = false;
        foreach (array_slice($merged, 0, 5) as $result) {
            if (($result['document_type'] ?? '') === 'webpage') {
                $hasWebpage = true;
                break;
            }
        }

        // If no webpages in top 5, explicitly search webpages and inject if relevant
        if (!$hasWebpage) {
            $webpageResults = $this->embeddingRepository->findSimilarChunksWebpageOnly(
                $embeddingOriginal['embedding'],
                5
            );

            foreach ($webpageResults as $wpResult) {
                // Only inject if distance is reasonable (0.8 = somewhat relevant)
                if ((float) $wpResult['distance'] <= 0.8) {
                    $merged = $this->injectWebpageResult($merged, $wpResult);
                }
            }
        }

        // 6. Apply threshold and limit
        return $this->applyThresholdAndLimit($merged, $distanceThreshold, $minResults, $limit);
    }

    /**
     * Merge two result sets using Reciprocal Rank Fusion (RRF)
     *
     * RRF is a simple and effective method for combining ranked lists.
     * Score = sum(1 / (k + rank)) where k is a constant (typically 60)
     *
     * @param array<array<string, mixed>> $results1
     * @param array<array<string, mixed>> $results2
     * @return array<array<string, mixed>>
     */
    private function mergeWithRRF(array $results1, array $results2, float $k = 60): array
    {
        /** @var array<string, float> $scores */
        $scores = [];
        /** @var array<string, array<string, mixed>> $dataByChunk */
        $dataByChunk = [];

        // Score from first result set
        foreach ($results1 as $rank => $result) {
            /** @var string $chunkId */
            $chunkId = $result['chunk_id'];
            $scores[$chunkId] = ($scores[$chunkId] ?? 0) + (1 / ($k + $rank + 1));
            $dataByChunk[$chunkId] = $result;
        }

        // Score from second result set
        foreach ($results2 as $rank => $result) {
            /** @var string $chunkId */
            $chunkId = $result['chunk_id'];
            $scores[$chunkId] = ($scores[$chunkId] ?? 0) + (1 / ($k + $rank + 1));

            // Keep the result with better distance
            /** @var float $newDistance */
            $newDistance = $result['distance'];
            /** @var float $existingDistance */
            $existingDistance = $dataByChunk[$chunkId]['distance'] ?? 999;
            if (!isset($dataByChunk[$chunkId]) || $newDistance < $existingDistance) {
                $dataByChunk[$chunkId] = $result;
            }
        }

        // Apply webpage boost AFTER RRF (1.5x = 50% boost)
        foreach ($scores as $chunkId => $score) {
            if (($dataByChunk[$chunkId]['document_type'] ?? '') === 'webpage') {
                $scores[$chunkId] = $score * 1.5;
            }
        }

        // Sort by RRF score (descending)
        arsort($scores);

        // Build final results
        $merged = [];
        foreach ($scores as $chunkId => $rrfScore) {
            $result = $dataByChunk[$chunkId];
            $result['rrf_score'] = $rrfScore;
            $merged[] = $result;
        }

        return $merged;
    }

    /**
     * Inject a webpage result into merged results if not already present
     *
     * @param array<array<string, mixed>> $merged
     * @param array<string, mixed> $wpResult
     * @return array<array<string, mixed>>
     */
    private function injectWebpageResult(array $merged, array $wpResult): array
    {
        /** @var string $chunkId */
        $chunkId = $wpResult['chunk_id'];

        // Check if already in results
        foreach ($merged as $result) {
            /** @var string $existingChunkId */
            $existingChunkId = $result['chunk_id'];
            if ($existingChunkId === $chunkId) {
                return $merged;
            }
        }

        // Add webpage result with a reasonable RRF score (between top results)
        $wpResult['rrf_score'] = 0.015; // Places it in top 10 typically
        $merged[] = $wpResult;

        // Re-sort by RRF score
        usort($merged, fn($a, $b) => ($b['rrf_score'] ?? 0) <=> ($a['rrf_score'] ?? 0));

        return $merged;
    }

    /**
     * Apply distance threshold and limit to results
     *
     * @param array<array<string, mixed>> $results
     * @return array<array<string, mixed>>
     */
    private function applyThresholdAndLimit(
        array $results,
        float $distanceThreshold,
        int $minResults,
        int $limit
    ): array {
        $relevant = [];
        $belowThreshold = [];

        foreach ($results as $result) {
            /** @var float $distance */
            $distance = $result['distance'];
            if ($distance <= $distanceThreshold) {
                $relevant[] = $result;
            } else {
                $belowThreshold[] = $result;
            }
        }

        // Ensure minimum results
        if (count($relevant) < $minResults && count($belowThreshold) > 0) {
            $needed = $minResults - count($relevant);
            $relevant = array_merge($relevant, array_slice($belowThreshold, 0, $needed));
        }

        return array_slice($relevant, 0, $limit);
    }

    /**
     * Preprocess query by removing question words and meaningless verbs
     * This improves embedding quality by focusing on semantic content
     */
    private function preprocessQueryForEmbedding(string $query): string
    {
        $words = preg_split('/\s+/', mb_strtolower(trim($query)), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || empty($words)) {
            return $query;
        }

        // Remove question words and meaningless verbs
        $filtered = array_filter($words, fn(string $word): bool =>
            !in_array($word, self::CZECH_QUESTION_WORDS, true) &&
            !in_array($word, self::MEANINGLESS_VERBS, true) &&
            mb_strlen($word) > 1
        );

        // If we filtered out everything, return original
        if (empty($filtered)) {
            return $query;
        }

        return implode(' ', $filtered);
    }

    /**
     * Apply aggressive Czech stemming to improve word matching
     * Strips common Czech suffixes while keeping minimum 4 chars
     */
    private function stemCzechWord(string $word): string
    {
        // Aggressive Czech noun/adjective suffixes - ordered by length (longest first)
        $suffixes = [
            // Long compound suffixes
            'ových', 'ovými', 'ovou', 'ového', 'ovému',
            'ními', 'ního', 'nímu', 'ním',
            'ová', 'ový', 'ové', 'ovým',
            // Case endings
            'ách', 'ami', 'ech', 'ům', 'ím',
            'ou', 'em', 'ů', 'ích',
            // Short endings
            'y', 'u', 'e', 'i', 'a',
        ];

        foreach ($suffixes as $suffix) {
            // Keep at least 4 chars after stripping
            if (mb_strlen($word) > mb_strlen($suffix) + 3 &&
                str_ends_with($word, $suffix)) {
                return mb_substr($word, 0, -mb_strlen($suffix));
            }
        }

        return $word;
    }

    /**
     * Expand short or vague queries with contextual terms for better search
     *
     * Short queries like "sport" get expanded to include related terms,
     * improving both vector similarity matching and keyword search.
     * Now also checks stemmed words against expansion keys.
     */
    private function expandQuery(string $query): string
    {
        $normalizedQuery = mb_strtolower(trim($query));

        // Check for exact matches in expansion map
        if (isset(self::QUERY_EXPANSIONS[$normalizedQuery])) {
            return self::QUERY_EXPANSIONS[$normalizedQuery] . ' Těrlicko';
        }

        // Check each word (and its stem) for expansion matches
        $words = preg_split('/\s+/', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY);
        if ($words !== false) {
            foreach ($words as $word) {
                // Check exact word match
                if (isset(self::QUERY_EXPANSIONS[$word])) {
                    return $query . ' ' . self::QUERY_EXPANSIONS[$word] . ' Těrlicko';
                }
                // Check stemmed word match
                $stemmed = $this->stemCzechWord($word);
                if ($stemmed !== $word && isset(self::QUERY_EXPANSIONS[$stemmed])) {
                    return $query . ' ' . self::QUERY_EXPANSIONS[$stemmed] . ' Těrlicko';
                }
            }

            // Check for partial matches (query contains expansion key)
            foreach (self::QUERY_EXPANSIONS as $key => $expansion) {
                if (str_contains($normalizedQuery, $key)) {
                    return $query . ' ' . $expansion . ' Těrlicko';
                }
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
