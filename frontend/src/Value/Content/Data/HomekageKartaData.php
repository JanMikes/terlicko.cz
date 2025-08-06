<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type OdkazDataArray from OdkazData
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-type HomekageKartaDataArray array{
 *     Nadpis: null|string,
 *     Text: null|string,
 *     Obrazek: null|ImageDataArray,
 *     ObrazekHover: null|ImageDataArray,
 *     Odkaz: null|OdkazDataArray,
 * }
 */
readonly final class HomekageKartaData
{
    /** @use CanCreateManyFromStrapiResponse<HomekageKartaDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public null|string $Nadpis,
        public null|string $Text,
        public null|ImageData $Obrazek,
        public null|ImageData $ObrazekHover,
        public null|OdkazData $Odkaz,
    ) {
    }

    /**
     * @param HomekageKartaDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Nadpis: $data['Nadpis'],
            Text: $data['Text'],
            Obrazek: $data['Obrazek'] !== null ? ImageData::createFromStrapiResponse($data['Obrazek']) : null,
            ObrazekHover: $data['ObrazekHover'] !== null ? ImageData::createFromStrapiResponse($data['ObrazekHover']) : null,
            Odkaz: $data['Odkaz'] !== null ? OdkazData::createFromStrapiResponse($data['Odkaz']) : null,
        );
    }
}
