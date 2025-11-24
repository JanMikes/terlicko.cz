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
     * @return array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, distance: float, keyword_rank: float, combined_score: float}>
     */
    public function findSimilarChunksHybrid(array $queryVector, string $keywords, int $limit = 10): array
    {
        $vectorString = '[' . implode(',', $queryVector) . ']';

        // Extract significant words (4+ chars) for ILIKE matching
        $keywordPatterns = $this->extractKeywordPatterns($keywords);

        // Build ILIKE conditions for keyword matching
        $ilikeConditions = [];
        $ilikeParams = [];
        foreach ($keywordPatterns as $index => $pattern) {
            $ilikeConditions[] = "c.content ILIKE :keyword_{$index}";
            $ilikeParams["keyword_{$index}"] = '%' . $pattern . '%';
        }

        // If no significant keywords, use vector-only search
        if (empty($ilikeConditions)) {
            $sql = <<<SQL
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
                    (1.0 / (ROW_NUMBER() OVER (ORDER BY e.vector <=> :query_vector::vector) + 10)) as combined_score
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

            /** @var array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, distance: float, keyword_rank: float, combined_score: float}> */
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
                LIMIT 50
            ),
            keyword_search AS (
                SELECT
                    c.id as chunk_id,
                    ({$matchCountExpr})::float as keyword_rank,
                    ROW_NUMBER() OVER (ORDER BY ({$matchCountExpr}) DESC) as rank
                FROM ai_chunks c
                WHERE {$ilikeWhere}
                LIMIT 50
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
                (COALESCE(1.0 / (vs.rank + 10), 0) + COALESCE(1.0 / (ks.rank + 10), 0)) as combined_score
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

        /** @var array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string, distance: float, keyword_rank: float, combined_score: float}> */
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

        // Czech stop words to exclude
        $stopWords = ['jsou', 'jaké', 'jaká', 'jaký', 'kde', 'kdy', 'jak', 'kdo', 'nebo', 'ale', 'tak', 'jen', 'již', 'jako', 'jeho', 'její', 'jsem', 'jste', 'jsme', 'být', 'mít', 'mám', 'máte', 'máme', 'pro', 'pod', 'nad', 'mezi', 'před', 'za', 'při', 'bez', 'toto', 'tato', 'tyto', 'této', 'tohoto', 'těchto', 'které', 'která', 'který', 'kterou', 'kterým', 'obci', 'obce'];

        return array_values(array_filter(
            $words,
            fn(string $word) => mb_strlen($word) >= 4 && !in_array($word, $stopWords, true)
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
