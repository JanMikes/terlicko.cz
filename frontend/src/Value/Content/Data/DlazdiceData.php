<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class DlazdiceData
{
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public readonly string|null $Ikona,
        public readonly string $Nadpis_dlazdice,
        public readonly string $Odkaz,
    ) {
    }

    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        return new self(
            $data['Ikona']['url'] ?? null,
            $data['Nadpis_dlazdice'],
            $data['Odkaz'],
        );
    }
}
