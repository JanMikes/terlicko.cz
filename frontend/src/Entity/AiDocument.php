<?php

declare(strict_types=1);

namespace Terlicko\Web\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\Table(name: 'ai_documents')]
#[ORM\Index(name: 'idx_ai_documents_source_url', columns: ['source_url'])]
#[ORM\Index(name: 'idx_ai_documents_content_hash', columns: ['content_hash'])]
#[ORM\Index(name: 'idx_ai_documents_updated_at', columns: ['updated_at'])]
class AiDocument
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private UuidInterface $id;

    #[ORM\Column(type: Types::STRING, length: 1000)]
    private string $sourceUrl;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $title;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $type; // 'pdf', 'webpage', etc.

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $contentHash; // SHA256 hash for change detection

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metadata = null; // JSON metadata

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, AiChunk> */
    #[ORM\OneToMany(targetEntity: AiChunk::class, mappedBy: 'document', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $chunks;

    public function __construct(
        string $sourceUrl,
        string $title,
        string $type,
        string $contentHash,
        ?string $metadata = null,
    ) {
        $this->sourceUrl = $sourceUrl;
        $this->title = $title;
        $this->type = $type;
        $this->contentHash = $contentHash;
        $this->metadata = $metadata;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->chunks = new ArrayCollection();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContentHash(): string
    {
        return $this->contentHash;
    }

    public function updateContentHash(string $contentHash): void
    {
        $this->contentHash = $contentHash;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getMetadata(): ?string
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return Collection<int, AiChunk> */
    public function getChunks(): Collection
    {
        return $this->chunks;
    }

    public function addChunk(AiChunk $chunk): void
    {
        if (!$this->chunks->contains($chunk)) {
            $this->chunks->add($chunk);
        }
    }
}
