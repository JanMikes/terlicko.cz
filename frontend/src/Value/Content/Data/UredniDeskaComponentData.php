<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type KategorieUredniDeskyDataArray from KategorieUredniDeskyData
 */
readonly final class UredniDeskaComponentData
{
    public function __construct(
        /** @var array<KategorieUredniDeskyData> $Kategorie */
        public array $Kategorie,
        public int $Pocet,
    ) {}

    /**
     * @param array{
     *     Pocet: int,
     *     kategorie_uredni_deskies: array<KategorieUredniDeskyDataArray>,
     * } $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            KategorieUredniDeskyData::createManyFromStrapiResponse($data['kategorie_uredni_deskies']),
            $data['Pocet'],
        );
    }
}
