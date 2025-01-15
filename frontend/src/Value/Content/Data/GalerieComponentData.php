<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ObrazekDataArray from ObrazekData
 */
readonly final class GalerieComponentData
{
    /**
     * @param array<ObrazekData> $Obrazek
     */
    public function __construct(
        public array $Obrazek,
        public int $Pocet_zobrazenych,
    ) {}

    /**
     * @param array{
     *     Obrazek: array<ObrazekDataArray>,
     *     Pocet_zobrazenych: int,
     *  } $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            ObrazekData::createManyFromStrapiResponse($data['Obrazek']),
            $data['Pocet_zobrazenych'],
        );
    }
}
