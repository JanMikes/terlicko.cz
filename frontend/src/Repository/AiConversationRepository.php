<?php

declare(strict_types=1);

namespace Terlicko\Web\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;
use Terlicko\Web\Entity\AiConversation;

/**
 * @extends ServiceEntityRepository<AiConversation>
 */
final class AiConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiConversation::class);
    }

    /**
     * Find active conversation for a guest
     */
    public function findActiveByGuestId(UuidInterface $guestId): ?AiConversation
    {
        /** @var AiConversation|null */
        return $this->createQueryBuilder('c')
            ->where('c.guestId = :guestId')
            ->andWhere('c.endedAt IS NULL')
            ->setParameter('guestId', $guestId, 'uuid')
            ->orderBy('c.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find conversation by ID for a specific guest
     */
    public function findByIdAndGuestId(UuidInterface $conversationId, UuidInterface $guestId): ?AiConversation
    {
        /** @var AiConversation|null */
        return $this->createQueryBuilder('c')
            ->where('c.id = :conversationId')
            ->andWhere('c.guestId = :guestId')
            ->setParameter('conversationId', $conversationId, 'uuid')
            ->setParameter('guestId', $guestId, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count active conversations started in a time period for a guest
     */
    public function countRecentByGuestId(UuidInterface $guestId, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.guestId = :guestId')
            ->andWhere('c.startedAt >= :since')
            ->setParameter('guestId', $guestId, 'uuid')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Delete old ended conversations (data retention)
     */
    public function deleteOlderThan(\DateTimeImmutable $date): int
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->where('c.endedAt IS NOT NULL')
            ->andWhere('c.endedAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Find all conversations for a guest, ordered by most recent first
     *
     * @return AiConversation[]
     */
    public function findAllByGuestId(UuidInterface $guestId, int $limit = 20): array
    {
        /** @var AiConversation[] */
        return $this->createQueryBuilder('c')
            ->where('c.guestId = :guestId')
            ->setParameter('guestId', $guestId, 'uuid')
            ->orderBy('c.startedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
