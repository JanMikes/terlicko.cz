<?php

declare(strict_types=1);

namespace Terlicko\Web\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Terlicko\Web\Entity\AiMessageFeedback;

/**
 * @extends ServiceEntityRepository<AiMessageFeedback>
 */
final class AiMessageFeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiMessageFeedback::class);
    }
}
