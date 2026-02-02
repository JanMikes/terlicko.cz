<?php

declare(strict_types=1);

namespace Terlicko\Web\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;
use Terlicko\Web\Repository\AiMessageFeedbackRepository;

#[ORM\Entity(repositoryClass: AiMessageFeedbackRepository::class)]
#[ORM\Table(name: 'ai_message_feedback')]
#[ORM\Index(name: 'idx_ai_message_feedback_message', columns: ['message_id'])]
#[ORM\Index(name: 'idx_ai_message_feedback_created', columns: ['created_at'])]
class AiMessageFeedback
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: AiMessage::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AiMessage $message;

    #[ORM\Column(type: Types::TEXT)]
    private string $feedbackText;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        AiMessage $message,
        string $feedbackText,
    ) {
        $this->message = $message;
        $this->feedbackText = $feedbackText;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getMessage(): AiMessage
    {
        return $this->message;
    }

    public function getFeedbackText(): string
    {
        return $this->feedbackText;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
