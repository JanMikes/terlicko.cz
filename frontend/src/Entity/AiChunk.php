<?php

declare(strict_types=1);

namespace Terlicko\Web\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\Table(name: 'ai_chunks')]
#[ORM\Index(name: 'idx_ai_chunks_document', columns: ['document_id'])]
class AiChunk
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: AiDocument::class, inversedBy: 'chunks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AiDocument $document;

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    #[ORM\Column(type: Types::INTEGER)]
    private int $chunkIndex; // Position in the document (0, 1, 2, ...)

    #[ORM\Column(type: Types::INTEGER)]
    private int $tokenCount;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metadata = null; // JSON metadata (e.g., page number for PDFs)

    #[ORM\OneToOne(targetEntity: AiEmbedding::class, mappedBy: 'chunk', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?AiEmbedding $embedding = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        AiDocument $document,
        string $content,
        int $chunkIndex,
        int $tokenCount,
        ?string $metadata = null,
    ) {
        $this->document = $document;
        $this->content = $content;
        $this->chunkIndex = $chunkIndex;
        $this->tokenCount = $tokenCount;
        $this->metadata = $metadata;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getDocument(): AiDocument
    {
        return $this->document;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getChunkIndex(): int
    {
        return $this->chunkIndex;
    }

    public function getTokenCount(): int
    {
        return $this->tokenCount;
    }

    public function getMetadata(): ?string
    {
        return $this->metadata;
    }

    public function getEmbedding(): ?AiEmbedding
    {
        return $this->embedding;
    }

    public function setEmbedding(AiEmbedding $embedding): void
    {
        $this->embedding = $embedding;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
