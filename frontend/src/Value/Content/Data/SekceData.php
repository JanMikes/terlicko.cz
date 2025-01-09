<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class SekceData
{
    public function __construct(
        public readonly string $Nazev,
        public readonly string|null $Meta_description,

        /** @var array<mixed> */
        public readonly array $Komponenty,
    ) {
    }

    public static function createFromStrapiResponse(array $data): self
    {
        // TODO: Upravit
        $komponenty = $data['Komponenty'];

        return new self(
            $data['Nazev'],
            $data['Meta_description'],
            $komponenty,
        );
    }
}
