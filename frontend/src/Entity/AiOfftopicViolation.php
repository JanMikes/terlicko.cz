<?php

declare(strict_types=1);

namespace Terlicko\Web\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: \Terlicko\Web\Repository\AiOfftopicViolationRepository::class)]
#[ORM\Table(name: 'ai_offtopic_violations')]
#[ORM\Index(name: 'idx_offtopic_guest_created', columns: ['guest_id', 'created_at'])]
class AiOfftopicViolation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private UuidInterface $id;

    #[ORM\Column(type: 'uuid')]
    private UuidInterface $guestId;

    #[ORM\Column(type: Types::TEXT)]
    private string $question;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(UuidInterface $guestId, string $question)
    {
        $this->guestId = $guestId;
        $this->question = $question;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getGuestId(): UuidInterface
    {
        return $this->guestId;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
