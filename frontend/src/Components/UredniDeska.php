<?php

declare(strict_types=1);

namespace Terlicko\Web\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Terlicko\Web\Services\Strapi\StrapiContent;
use Terlicko\Web\Value\Content\Data\UredniDeskaData;

#[AsTwigComponent]
readonly final class UredniDeska
{
    public function __construct(
        private StrapiContent $content,
    ) {
    }

    /**
     * @return array<UredniDeskaData>
     */
    public function getItems(): array
    {
        return $this->content->getUredniDeskyData();
    }
}
