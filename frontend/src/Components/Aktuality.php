<?php

declare(strict_types=1);

namespace Terlicko\Web\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Terlicko\Web\Services\Strapi\StrapiContent;
use Terlicko\Web\Value\Content\Data\AktualitaData;

#[AsTwigComponent]
readonly final class Aktuality
{
    public function __construct(
        private StrapiContent $content,
    ) {
    }

    /**
     * @return array<AktualitaData>
     */
    public function getItems(): array
    {
        return $this->content->getAktualityData();
    }
}
