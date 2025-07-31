<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type OdkazDataArray from OdkazData
 * @phpstan-type ObrazekDataArray array{
 *     Obrazek: null|ImageDataArray,
 *     Odkaz: null|OdkazDataArray,
 * }
 */
readonly final class ObrazekData
{
    /** @use CanCreateManyFromStrapiResponse<ObrazekDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public null|ImageData $Obrazek,
        public null|OdkazData $Odkaz,
    ) {
    }

    /**
     * @param ObrazekDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['Obrazek'] !== null ? ImageData::createFromStrapiResponse($data['Obrazek']) : null,
            $data['Odkaz'] !== null ? OdkazData::createFromStrapiResponse($data['Odkaz']) : null,
        );
    }
}
