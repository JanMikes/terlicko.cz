<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ClovekDataArray from ClovekData
 */
readonly final class SamospravaComponentData
{
    /**
     * @param array<ClovekData> $lide
     */
    public function __construct(
        public array $lide,
    ) {}

    /**
     * @param array{lides: array<ClovekDataArray>} $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            ClovekData::createManyFromStrapiResponse($data['lides']),
        );
    }
}
