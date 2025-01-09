<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class BannerSTlacitkamaData
{
    public function __construct(
        public readonly string $Nadpis,
        public readonly string $Obrazek,
        /**
         * @var array<TlacitkoData> $Tlacitka
         */
        public readonly array $Tlacitka,
    ) {}
}
