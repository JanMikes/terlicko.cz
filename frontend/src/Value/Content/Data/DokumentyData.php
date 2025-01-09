<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class DokumentyData
{
    use HasUredniDeskaYears;

    public readonly array $Uredni_deska_roky;

    public function __construct(
        public readonly string|null $Nadpis,
        public readonly string|null $Obsah,

        /**
         * @var array<UredniDeskaData> $Uredni_deska
         */
        public readonly array $Uredni_deska,

        /**
         * @var array<int>
         */
    ) {
        $this->Uredni_deska_roky = self::getUredniDeskaYears($Uredni_deska);
    }
}
