<?php

declare(strict_types=1);

namespace Terlicko\Web\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Terlicko\Web\Services\Strapi\StrapiContent;
use Terlicko\Web\Value\Content\Data\FooterData;

#[AsTwigComponent]
readonly final class Footer
{
    public function __construct(
        private StrapiContent $content,
    ) {
    }

    public function getItems(): null|FooterData
    {
        return $this->content->getFooterData();
    }
}
