<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class SluzbyData
{
    public function __construct(
        public readonly BannerSTlacitkamaData $BannerSTlacitkama,

        /**
         * @var array<SluzbaData> $Sluzby
         */
        public readonly array $Sluzby,
    ) {}
}
