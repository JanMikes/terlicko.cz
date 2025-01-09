<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class SekceSDlazdicemaData
{
    public function __construct(
        public readonly string $Nadpis,
        public readonly string|null $Popis,

        /**
         * @var array<DlazdiceData> $Dlazdice
         */
        public readonly array $Dlazdice,
    )
    {
    }

    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        return new self(
            $data['Nadpis'],
            $data['Popis'],
            DlazdiceData::createManyFromStrapiResponse($data['Dlazdice']),
        );
    }
}
