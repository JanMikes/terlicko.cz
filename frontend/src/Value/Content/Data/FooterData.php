<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class FooterData
{
    public function __construct(
        public readonly string $Odkaz,
        public readonly string|null $Obrazek,
    ) {}
}
