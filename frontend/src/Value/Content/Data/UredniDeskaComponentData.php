<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

readonly final class UredniDeskaComponentData
{
    public function __construct(
        public int $Pocet,
        public KategorieUredniDesky $Kategorie,
    ) {}

    /**
     * @param array{
     *     Pocet: int,
     *     Kategorie: string,
     * } $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        $kategorie = KategorieUredniDesky::from($data['Kategorie']);

        return new self(
            $data['Pocet'],
            $kategorie,
        );
    }
}
