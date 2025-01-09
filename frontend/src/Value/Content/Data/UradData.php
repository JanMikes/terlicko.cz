<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class UradData
{
    public function __construct(
        public readonly BannerSTextemData $Banner,
        public readonly SekceSDlazdicemaData $Sekce_s_dlazdicema,
    )
    {
    }
}
