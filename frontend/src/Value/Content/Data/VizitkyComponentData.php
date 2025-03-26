<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type VizitkaDataArray from VizitkaData
 */
readonly final class VizitkyComponentData
{
    /**
     * @param array<VizitkaData> $Vizitky
     */
    public function __construct(
        public array $Vizitky,
    ) {}

    /**
     * @param array{Vizitky: array<VizitkaDataArray>} $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            VizitkaData::createManyFromStrapiResponse($data['Vizitky']),
        );
    }
}
