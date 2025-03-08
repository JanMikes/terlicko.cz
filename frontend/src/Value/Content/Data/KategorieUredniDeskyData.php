<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type KategorieUredniDeskyDataArray array{
 *     id: int,
 *     Nazev: string,
 *     slug: string,
 *  }
 */
readonly final class KategorieUredniDeskyData
{
    /** @use CanCreateManyFromStrapiResponse<KategorieUredniDeskyDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public int $id,
        public string $Nazev,
        public string $slug,
    ) {
    }

    /**
     * @param KategorieUredniDeskyDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['id'],
            $data['Nazev'],
            $data['slug'],
        );
    }
}
