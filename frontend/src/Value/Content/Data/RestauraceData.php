<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class RestauraceData
{
    public function __construct(
        public readonly BannerSTextemData $BannerSTextem,
        /**
         * @var array<KartaObjektuData> $Restaurace
         */
        public readonly array $Restaurace,
    ) {}
}
