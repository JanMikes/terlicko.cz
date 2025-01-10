<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

readonly final class MenuData
{
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $Nadpis,
        public string $Odkaz,
    ) {
    }

    /**
     * @param array{
     *     Nadpis: string,
     *     Odkaz: string,
     * } $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        $link = ltrim($data['Odkaz'], '/');

        if (str_starts_with($link, 'http') !== true) {
            $link = '/' . $link;
        }

        return new self(
            Nadpis: $data['Nadpis'],
            Odkaz: $link,
        );
    }
}
