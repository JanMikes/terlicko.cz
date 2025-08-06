<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type TlacitkoDataArray from TlacitkoData
 * @phpstan-type SlideDataArray array{
 *      Titulek: null|string,
 *      Nadpis: null|string,
 *      Text: null|string,
 *      Tlacitko: null|TlacitkoDataArray,
 *      Obrazek: null|ImageDataArray,
 *  }
 */
readonly final class SlideData
{
    /** @use CanCreateManyFromStrapiResponse<SlideDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public null|string $Titulek,
        public null|string $Nadpis,
        public null|string $Text,
        public null|TlacitkoData $Tlacitko,
        public null|ImageData $Obrazek,
    ) {}

    /**
     * @param SlideDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        $obrazek = $data['Obrazek'] !== null ? ImageData::createFromStrapiResponse($data['Obrazek']) : null;
        $tlacitko = $data['Tlacitko'] !== null ? TlacitkoData::createFromStrapiResponse($data['Tlacitko']) : null;

        return new self(
            Titulek: $data['Titulek'],
            Nadpis: $data['Nadpis'],
            Text: $data['Text'],
            Tlacitko: $tlacitko,
            Obrazek: $obrazek,
        );
    }
}
