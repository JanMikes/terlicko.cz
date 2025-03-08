<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ClovekSamospravyDataArray from ClovekSamospravyData
 */
readonly final class SamospravaComponentData
{
    /**
     * @param array<ClovekSamospravyData> $lide
     */
    public function __construct(
        public array $lide,
    ) {}

    /**
     * @param array{Lide: array<ClovekSamospravyDataArray>} $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            ClovekSamospravyData::createManyFromStrapiResponse($data['Lide']),
        );
    }
}
