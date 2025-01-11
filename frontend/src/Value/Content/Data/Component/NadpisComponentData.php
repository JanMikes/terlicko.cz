<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data\Component;

readonly final class NadpisComponentData
{
    public function __construct(
        public string $Nadpis,
        public string $Typ,
    ) {}

    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['Nadpis'],
            $data['Typ'],
        );
    }
}
