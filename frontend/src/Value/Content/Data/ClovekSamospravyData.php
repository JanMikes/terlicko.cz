<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type ClovekDataArray from ClovekData
 * @phpstan-type ClovekSamospravyDataArray array{
 *     Funkce: null|string,
 *     lide: null|ClovekDataArray,
 *  }
 */
readonly final class ClovekSamospravyData
{
    /** @use CanCreateManyFromStrapiResponse<ClovekSamospravyDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public null|string $Funkce,
        public null|ClovekData $clovek,
    ) {
    }


    /**
     * @param ClovekSamospravyDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['Funkce'],
            $data['lide'] !== null ? ClovekData::createFromStrapiResponse($data['lide']) : null,
        );
    }
}
