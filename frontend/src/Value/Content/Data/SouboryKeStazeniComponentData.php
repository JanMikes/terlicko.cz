<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type SouborDataArray from SouborData
 */
readonly final class SouboryKeStazeniComponentData
{
    /**
     * @param array<SouborData> $Soubor
     */
    public function __construct(
        public int $Pocet_sloupcu,
        public array $Soubor,
    ) {}

    /**
     * @param array{
     *     Pocet_sloupcu: string,
     *     Soubor: array<SouborDataArray>,
     *  } $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        $pocetSloupcu = 1;

        if ($data['Pocet_sloupcu'] === 'Dva') {
            $pocetSloupcu = 2;
        }

        return new self(
            $pocetSloupcu,
            SouborData::createManyFromStrapiResponse($data['Soubor']),
        );
    }
}
