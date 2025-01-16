<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type OdkazDataArray from OdkazData
 * @phpstan-type MenuDataArray array{
 *     Nadpis: string,
 *     Odkaz: OdkazDataArray,
 * }
 */
readonly final class MenuData
{
    /** @use CanCreateManyFromStrapiResponse<MenuDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $Nadpis,
        public OdkazData $Odkaz,
    ) {
    }

    /**
     * @param MenuDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Nadpis: $data['Nadpis'],
            Odkaz: OdkazData::createFromStrapiResponse($data['Odkaz']),
        );
    }
}
