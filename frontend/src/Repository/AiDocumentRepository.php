<?php

declare(strict_types=1);

namespace Terlicko\Web\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Terlicko\Web\Entity\AiDocument;

/**
 * @extends ServiceEntityRepository<AiDocument>
 */
final class AiDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiDocument::class);
    }

    /**
     * Find document by source URL
     */
    public function findBySourceUrl(string $sourceUrl): ?AiDocument
    {
        /** @var AiDocument|null */
        return $this->createQueryBuilder('d')
            ->where('d.sourceUrl = :sourceUrl')
            ->setParameter('sourceUrl', $sourceUrl)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Check if document has changed by comparing content hash
     */
    public function hasDocumentChanged(string $sourceUrl, string $currentHash): bool
    {
        $document = $this->findBySourceUrl($sourceUrl);

        if ($document === null) {
            return true; // New document
        }

        return $document->getContentHash() !== $currentHash;
    }

    /**
     * Get all documents ordered by last update
     *
     * @return array<AiDocument>
     */
    public function findAllOrderedByUpdate(): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
