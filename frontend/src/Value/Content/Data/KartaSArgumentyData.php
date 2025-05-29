<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type TlacitkoDataArray from TlacitkoData
 * @phpstan-type KartaSArgumentyDataArray array{
 *     Nadpis: null|string,
 *     Umisteni_nadpisu: null|string,
 *     Text: null|string,
 *     Obrazek: null|ImageDataArray,
 *     Tlacitko: null|TlacitkoDataArray,
 *  }
 */
readonly final class KartaSArgumentyData
{
    /** @use CanCreateManyFromStrapiResponse<KartaSArgumentyDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public null|string $Nadpis,
        public null|string $Umisteni_nadpisu,
        public null|string $Text,
        public null|ImageData $Obrazek,
        public null|TlacitkoData $Tlacitko,
    ) {}

    /**
     * @param KartaSArgumentyDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Nadpis: $data['Nadpis'],
            Umisteni_nadpisu: $data['Umisteni_nadpisu'],
            Text: $data['Text'],
            Obrazek: $data['Obrazek'] !== null ? ImageData::createFromStrapiResponse($data['Obrazek']) : null,
            Tlacitko: $data['Tlacitko'] !== null ? TlacitkoData::createFromStrapiResponse($data['Tlacitko']) : null,
        );
    }
}
