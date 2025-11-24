<?php

declare(strict_types=1);

namespace Terlicko\Web\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: \Terlicko\Web\Repository\AiConversationRepository::class)]
#[ORM\Table(name: 'ai_conversations')]
#[ORM\Index(columns: ['guest_id'], name: 'idx_ai_conversations_guest')]
#[ORM\Index(columns: ['started_at'], name: 'idx_ai_conversations_started')]
#[ORM\Index(columns: ['ended_at'], name: 'idx_ai_conversations_ended')]
class AiConversation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private UuidInterface $id;

    #[ORM\Column(type: 'uuid')]
    private UuidInterface $guestId;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    /** @var Collection<int, AiMessage> */
    #[ORM\OneToMany(targetEntity: AiMessage::class, mappedBy: 'conversation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    public function __construct(
        UuidInterface $guestId,
        ?string $ipAddress = null,
    ) {
        $this->guestId = $guestId;
        $this->ipAddress = $ipAddress;
        $this->startedAt = new \DateTimeImmutable();
        $this->messages = new ArrayCollection();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getGuestId(): UuidInterface
    {
        return $this->guestId;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function end(): void
    {
        $this->endedAt = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->endedAt === null;
    }

    /** @return Collection<int, AiMessage> */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(AiMessage $message): void
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
        }
    }

    public function getMessageCount(): int
    {
        return $this->messages->count();
    }
}
