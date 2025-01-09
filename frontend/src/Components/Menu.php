<?php

declare(strict_types=1);

namespace Terlicko\Web\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Terlicko\Web\Services\Strapi\StrapiContent;

#[AsTwigComponent]
final class Menu
{
    public function __construct(
        private StrapiContent $content,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getItems(): array
    {
        return $this->content->getMenu();
    }
}
