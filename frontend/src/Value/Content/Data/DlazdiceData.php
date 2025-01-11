<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-type DlazdiceDataArray array{
 *     Ikona: ImageDataArray,
 *     Nadpis_dlazdice: string,
 *     Odkaz: string,
 * }
 */
readonly final class DlazdiceData
{
    /** @use CanCreateManyFromStrapiResponse<DlazdiceDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public ImageData $Ikona,
        public string $Nadpis_dlazdice,
        public string $Odkaz,
    ) {
    }

    /**
     * @param DlazdiceDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            ImageData::createFromStrapiResponse($data['Ikona']),
            $data['Nadpis_dlazdice'],
            $data['Odkaz'],
        );
    }
}
