<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class PristupnostData
{
    public function __construct(
        public string $Nadpis,
        public string $Obsah,
    ) {}
}
