<?php

declare(strict_types=1);

namespace Terlicko\Web\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;
use Terlicko\Web\Entity\AiOfftopicViolation;

/**
 * @extends ServiceEntityRepository<AiOfftopicViolation>
 */
final class AiOfftopicViolationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiOfftopicViolation::class);
    }

    public function countRecentByGuestId(UuidInterface $guestId, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.guestId = :guestId')
            ->andWhere('v.createdAt >= :since')
            ->setParameter('guestId', $guestId, 'uuid')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteOlderThan(\DateTimeImmutable $date): int
    {
        return $this->createQueryBuilder('v')
            ->delete()
            ->where('v.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
