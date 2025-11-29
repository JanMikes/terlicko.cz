<?php

declare(strict_types=1);

namespace Terlicko\Web\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Terlicko\Web\Entity\AiEmbedding;

/**
 * @extends ServiceEntityRepository<AiEmbedding>
 */
final class AiEmbeddingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiEmbedding::class);
    }

    /**
     * Find similar chunks using vector similarity search
     *
     * @param array<float> $queryVector
     * @param int $limit
     * @return array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, distance: float}>
     */
    public function findSimilarChunks(array $queryVector, int $limit = 10): array
    {
        $vectorString = '[' . implode(',', $queryVector) . ']';

        $sql = <<<SQL
            SELECT
                c.id as chunk_id,
                d.id as document_id,
                c.content,
                d.source_url,
                d.title,
                d.type as document_type,
                c.metadata as chunk_metadata,
                (e.vector <=> :query_vector::vector) as distance
            FROM ai_embeddings e
            INNER JOIN ai_chunks c ON c.id = e.chunk_id
            INNER JOIN ai_documents d ON d.id = c.document_id
            ORDER BY e.vector <=> :query_vector::vector
            LIMIT :limit
        SQL;

        $conn = $this->getEntityManager()->getConnection();
        $result = $conn->executeQuery($sql, [
            'query_vector' => $vectorString,
            'limit' => $limit,
        ]);

        /** @var array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, distance: float}> */
        return $result->fetchAllAssociative();
    }

    /**
     * Find similar chunks with hybrid search (vector + keyword)
     *
     * Uses ILIKE for keyword matching which works better for Czech language
     * by matching substrings (e.g., "fotbal" matches "fotbalovému", "Fotbalový")
     *
     * @param array<float> $queryVector
     * @param string $keywords
     * @param int $limit
     * @return array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, document_type: string, distance: float, keyword_rank: float, combined_score: float}>
     */
    public function findSimilarChunksHybrid(array $queryVector, string $keywords, int $limit = 10): array
    {
        $vectorString = '[' . implode(',', $queryVector) . ']';

        // Extract significant words for ILIKE matching
        $keywordPatterns = $this->extractKeywordPatterns($keywords);

        // Build ILIKE conditions for keyword matching
        $ilikeConditions = [];
        $ilikeParams = [];
        foreach ($keywordPatterns as $index => $pattern) {
            $ilikeConditions[] = "c.content ILIKE :keyword_{$index}";
            $ilikeParams["keyword_{$index}"] = '%' . $pattern . '%';
        }

        // If no significant keywords, use vector-only search with webpage boost
        if (empty($ilikeConditions)) {
            $sql = <<<SQL
                WITH ranked AS (
                    SELECT
                        c.id as chunk_id,
                        d.id as document_id,
                        c.content,
                        d.source_url,
                        d.title,
                        d.type as document_type,
                        c.metadata as chunk_metadata,
                        (e.vector <=> :query_vector::vector) as distance,
                        0::float as keyword_rank,
                        ROW_NUMBER() OVER (ORDER BY e.vector <=> :query_vector::vector) as rank
                    FROM ai_embeddings e
                    INNER JOIN ai_chunks c ON c.id = e.chunk_id
                    INNER JOIN ai_documents d ON d.id = c.document_id
                )
                SELECT
                    chunk_id, document_id, content, source_url, title, document_type, chunk_metadata,
                    distance, keyword_rank,
                    -- Webpage boost: webpages get 20% score boost over PDFs
                    (1.0 / (rank + 10)) * (CASE WHEN document_type = 'webpage' THEN 1.2 ELSE 1.0 END) as combined_score
                FROM ranked
                ORDER BY combined_score DESC
                LIMIT :limit
            SQL;

            $conn = $this->getEntityManager()->getConnection();
            $result = $conn->executeQuery($sql, [
                'query_vector' => $vectorString,
                'limit' => $limit,
            ]);

            /** @var array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, document_type: string, distance: float, keyword_rank: float, combined_score: float}> */
            return $result->fetchAllAssociative();
        }

        $ilikeWhere = implode(' OR ', $ilikeConditions);
        // Count matching keywords to rank results
        $matchCountExpr = implode(' + ', array_map(
            fn($i) => "(CASE WHEN c.content ILIKE :keyword_{$i} THEN 1 ELSE 0 END)",
            array_keys($keywordPatterns)
        ));

        $sql = <<<SQL
            WITH vector_search AS (
                SELECT
                    c.id as chunk_id,
                    (e.vector <=> :query_vector::vector) as distance,
                    ROW_NUMBER() OVER (ORDER BY e.vector <=> :query_vector::vector) as rank
                FROM ai_embeddings e
                INNER JOIN ai_chunks c ON c.id = e.chunk_id
                ORDER BY distance
                LIMIT 100
            ),
            keyword_search AS (
                SELECT
                    c.id as chunk_id,
                    ({$matchCountExpr})::float as keyword_rank,
                    ROW_NUMBER() OVER (ORDER BY ({$matchCountExpr}) DESC) as rank
                FROM ai_chunks c
                WHERE {$ilikeWhere}
                LIMIT 100
            )
            SELECT
                COALESCE(vs.chunk_id, ks.chunk_id) as chunk_id,
                d.id as document_id,
                c.content,
                d.source_url,
                d.title,
                d.type as document_type,
                c.metadata as chunk_metadata,
                COALESCE(vs.distance, 999) as distance,
                COALESCE(ks.keyword_rank, 0) as keyword_rank,
                -- Weighted scoring: 55% semantic (vector), 35% keyword, plus webpage boost
                -- Webpages get 20% boost to prioritize official website content over PDF documents
                (0.55 * COALESCE(1.0 / (vs.rank + 10), 0) + 0.35 * COALESCE(1.0 / (ks.rank + 10), 0))
                * (CASE WHEN d.type = 'webpage' THEN 1.2 ELSE 1.0 END) as combined_score
            FROM vector_search vs
            FULL OUTER JOIN keyword_search ks ON vs.chunk_id = ks.chunk_id
            INNER JOIN ai_chunks c ON c.id = COALESCE(vs.chunk_id, ks.chunk_id)
            INNER JOIN ai_documents d ON d.id = c.document_id
            ORDER BY combined_score DESC
            LIMIT :limit
        SQL;

        $conn = $this->getEntityManager()->getConnection();
        $params = array_merge(
            ['query_vector' => $vectorString, 'limit' => $limit],
            $ilikeParams
        );
        $result = $conn->executeQuery($sql, $params);

        /** @var array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, document_type: string, distance: float, keyword_rank: float, combined_score: float}> */
        return $result->fetchAllAssociative();
    }

    /**
     * Extract significant keywords from query for ILIKE matching
     *
     * @return array<int, string>
     */
    private function extractKeywordPatterns(string $query): array
    {
        // Remove punctuation and split into words
        $cleanQuery = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);
        if ($cleanQuery === null) {
            return [];
        }

        $words = preg_split('/\s+/', mb_strtolower($cleanQuery), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false) {
            return [];
        }

        // Important domain-specific terms that should always be included (even if short)
        $alwaysInclude = [
            // Sports
            'sport', 'tj', 'sk', 'fc', 'hc', 'fotbal', 'hokej', 'tenis', 'golf', 'volejbal', 'házená',
            // Places
            'hala', 'bazén', 'hřiště', 'stadion', 'les', 'park', 'přehrada',
            // Services
            'škola', 'školka', 'lékař', 'pošta', 'banka', 'obchod', 'knihovna',
            // Administration
            'úřad', 'starosta', 'rada', 'odbor', 'matrika',
            // Events
            'akce', 'ples', 'koncert', 'trhy', 'festival',
            // Utilities
            'voda', 'plyn', 'elektřina', 'odpad', 'svoz',
        ];

        // Comprehensive Czech stop words list
        $stopWords = [
            // Single-letter prepositions and conjunctions
            'a', 'i', 'k', 'o', 's', 'u', 'v', 'z',
            // Two-letter prepositions and common words
            'do', 'ke', 'ku', 'na', 'od', 'po', 've', 'za', 'ze', 'se', 'si', 'by', 'to', 'co', 'je', 'že', 'ta', 'tu', 'ty',
            // Three-letter words
            'ale', 'ani', 'asi', 'jak', 'kde', 'kdy', 'kdo', 'než', 'pod', 'pro', 'tak', 'ten', 'při', 'být', 'mít', 'jen', 'již', 'pak', 'dne',
            // Common function words
            'nebo', 'jako', 'jeho', 'její', 'jsem', 'jste', 'jsme', 'jsou', 'mám', 'máte', 'máme', 'mezi', 'před', 'toto', 'tato', 'tyto', 'této', 'tohoto', 'těchto',
            // Question words (keep some for intent)
            'jaké', 'jaká', 'jaký', 'které', 'která', 'který', 'kterou', 'kterým',
            // Other common words
            'tedy', 'však', 'což', 'přece', 'sice', 'zda', 'neboť', 'protože', 'pokud', 'kdyby', 'když', 'jestli',
            // Municipality-specific (too common in this context)
            'obci', 'obce', 'obecní',
        ];

        return array_values(array_filter(
            $words,
            fn(string $word) => in_array($word, $alwaysInclude, true) ||
                (mb_strlen($word) >= 3 && !in_array($word, $stopWords, true))
        ));
    }

    /**
     * Create vector index for faster similarity searches
     */
    public function createVectorIndex(): void
    {
        $sql = <<<SQL
            CREATE INDEX IF NOT EXISTS ai_embeddings_vector_idx
            ON ai_embeddings
            USING ivfflat (vector vector_cosine_ops)
            WITH (lists = 100)
        SQL;

        $this->getEntityManager()->getConnection()->executeStatement($sql);
    }
}
