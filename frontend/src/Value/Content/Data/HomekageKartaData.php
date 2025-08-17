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
 *     Obrazek_hover: null|ImageDataArray,
 *     Odkaz: null|OdkazDataArray,
 *     index?: null|int,
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
        public null|int $index,
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
            ObrazekHover: $data['Obrazek_hover'] !== null ? ImageData::createFromStrapiResponse($data['Obrazek_hover']) : null,
            Odkaz: $data['Odkaz'] !== null ? OdkazData::createFromStrapiResponse($data['Odkaz']) : null,
            index: $data['index'] ?? null,
        );
    }
}
