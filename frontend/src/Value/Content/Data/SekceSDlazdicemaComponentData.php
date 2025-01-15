<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type DlazdiceDataArray from DlazdiceData
 */
readonly final class SekceSDlazdicemaComponentData
{
    /**
     * @param array<DlazdiceData> $Dlazdice
     */
    public function __construct(
        public array $Dlazdice,
    ) {}

    /**
     * @param array{Dlazdice: array<DlazdiceDataArray>} $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            DlazdiceData::createManyFromStrapiResponse($data['Dlazdice']),
        );
    }
}
