<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-type IkonkaDataArray array{
 *     Ikonka: null|ImageDataArray,
 *     Nazev: null|string,
 * }
 */
readonly final class IkonkaData
{
    /** @use CanCreateManyFromStrapiResponse<IkonkaDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public null|ImageData $Ikonka,
        public null|string $Nazev,
    ) {
    }

    /**
     * @param IkonkaDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Ikonka: $data['Ikonka'] ? ImageData::createFromStrapiResponse($data['Ikonka']) : null,
            Nazev: $data['Nazev'],
        );
    }
}
