<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-type ObrazekGalerieDataArray array{
 *     Obrazek: ImageDataArray,
 *     Popis: string,
 * }
 */
readonly final class ObrazekGalerieData
{
    /** @use CanCreateManyFromStrapiResponse<ObrazeGaleriekDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public ImageData $Obrazek,
        public string $Popis,
    ) {
    }

    /**
     * @param ObrazekGalerieDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            ImageData::createFromStrapiResponse($data['Obrazek']),
            $data['Popis'],
        );
    }
}
