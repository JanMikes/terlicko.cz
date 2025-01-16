<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ObrazekGalerieDataArray from ObrazekGalerieData
 */
readonly final class GalerieComponentData
{
    /**
     * @param array<ObrazekGalerieData> $Obrazek
     */
    public function __construct(
        public array $Obrazek,
        public int $Pocet_zobrazenych,
    ) {}

    /**
     * @param array{
     *     Obrazek: array<ObrazekGalerieDataArray>,
     *     Pocet_zobrazenych: int,
     *  } $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            ObrazekGalerieData::createManyFromStrapiResponse($data['Obrazek']),
            $data['Pocet_zobrazenych'],
        );
    }
}
