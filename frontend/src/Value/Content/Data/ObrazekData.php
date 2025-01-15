<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-type ObrazekDataArray array{
 *     Obrazek: ImageDataArray,
 *     Popis: string,
 * }
 */
readonly final class ObrazekData
{
    /** @use CanCreateManyFromStrapiResponse<ObrazekDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public ImageData $Obrazek,
        public string $Popis,
    ) {
    }

    /**
     * @param ObrazekDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            ImageData::createFromStrapiResponse($data['Obrazek']),
            $data['Popis'],
        );
    }
}
