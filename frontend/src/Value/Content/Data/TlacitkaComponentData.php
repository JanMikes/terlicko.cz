<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type TlacitkoDataArray from TlacitkoData
 */
readonly final class TlacitkaComponentData
{
    /**
     * @param array<TlacitkoData> $Tlacitka
     */
    public function __construct(
        public array $Tlacitka,
    ) {}

    /**
     * @param array<TlacitkoDataArray> $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            TlacitkoData::createManyFromStrapiResponse($data['Tlacitka']),
        );
    }
}
