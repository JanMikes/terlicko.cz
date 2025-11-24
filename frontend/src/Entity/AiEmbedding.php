<?php

declare(strict_types=1);

namespace Terlicko\Web\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: \Terlicko\Web\Repository\AiEmbeddingRepository::class)]
#[ORM\Table(name: 'ai_embeddings')]
#[ORM\Index(name: 'idx_ai_embeddings_chunk', columns: ['chunk_id'])]
class AiEmbedding
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private UuidInterface $id;

    #[ORM\OneToOne(targetEntity: AiChunk::class, inversedBy: 'embedding')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AiChunk $chunk;

    /**
     * Vector embedding stored as pgvector
     * Format: [1.0, 2.0, 3.0, ...]
     */
    #[ORM\Column(type: Types::TEXT, columnDefinition: 'vector(1536)')]
    private string $vector;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $model; // e.g., 'text-embedding-3-small'

    #[ORM\Column(type: Types::INTEGER)]
    private int $dimensions; // e.g., 1536 for text-embedding-3-small

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @param array<float> $vectorArray
     */
    public function __construct(
        AiChunk $chunk,
        array $vectorArray,
        string $model,
        int $dimensions,
    ) {
        $this->chunk = $chunk;
        $this->vector = $this->arrayToVector($vectorArray);
        $this->model = $model;
        $this->dimensions = $dimensions;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getChunk(): AiChunk
    {
        return $this->chunk;
    }

    public function getVector(): string
    {
        return $this->vector;
    }

    /**
     * Get vector as array
     * @return array<float>
     */
    public function getVectorArray(): array
    {
        return $this->vectorToArray($this->vector);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getDimensions(): int
    {
        return $this->dimensions;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Convert array to pgvector format: [1.0, 2.0, 3.0]
     * @param array<float> $array
     */
    private function arrayToVector(array $array): string
    {
        return '[' . implode(',', $array) . ']';
    }

    /**
     * Convert pgvector format to array
     * @return array<float>
     */
    private function vectorToArray(string $vector): array
    {
        $trimmed = trim($vector, '[]');
        $parts = explode(',', $trimmed);
        return array_map('floatval', $parts);
    }
}
