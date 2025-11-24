<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Custom DBAL type for pgvector
 */
final class VectorType extends Type
{
    public const VECTOR = 'vector';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $dimensions = $column['dimensions'] ?? 1536; // Default for text-embedding-3-small
        return sprintf('vector(%d)', $dimensions);
    }

    public function getName(): string
    {
        return self::VECTOR;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?string
    {
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
