<?php

declare(strict_types=1);

namespace Terlicko\Web\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\Table(name: 'ai_messages')]
#[ORM\Index(name: 'idx_ai_messages_conversation', columns: ['conversation_id'])]
#[ORM\Index(name: 'idx_ai_messages_created', columns: ['created_at'])]
class AiMessage
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: AiConversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AiConversation $conversation;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $role; // 'user' or 'assistant'

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $citations = null; // JSON array of citations

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metadata = null; // JSON metadata (model used, tokens, etc.)

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        AiConversation $conversation,
        string $role,
        string $content,
        ?string $citations = null,
        ?string $metadata = null,
    ) {
        $this->conversation = $conversation;
        $this->role = $role;
        $this->content = $content;
        $this->citations = $citations;
        $this->metadata = $metadata;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getConversation(): AiConversation
    {
        return $this->conversation;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCitations(): ?string
    {
        return $this->citations;
    }

    public function getMetadata(): ?string
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistantMessage(): bool
    {
        return $this->role === 'assistant';
    }
}
