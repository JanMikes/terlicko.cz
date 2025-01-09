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

    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        $link = ltrim($data['Odkaz'], '/');

        if (str_starts_with($link, 'http') !== true) {
            $link = '/' . $link;
        }

        return new self(
            $data['Nadpis'],
            $link,
        );
    }
}
