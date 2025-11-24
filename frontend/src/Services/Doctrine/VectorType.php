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
        $dimensions = isset($column['dimensions']) && is_numeric($column['dimensions'])
            ? (int) $column['dimensions']
            : 1536; // Default for text-embedding-3-small
        return sprintf('vector(%d)', $dimensions);
    }

    public function getName(): string
    {
        return self::VECTOR;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        assert(is_string($value) || is_numeric($value));

        return (string) $value;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        assert(is_string($value) || is_numeric($value));

        return (string) $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
