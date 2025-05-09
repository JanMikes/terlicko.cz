<?php

declare(strict_types=1);

namespace Terlicko\Web\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Terlicko\Web\Services\Strapi\StrapiContent;
use Terlicko\Web\Value\Content\Data\KategorieUredniDeskyData;
use Terlicko\Web\Value\Content\Data\TagData;
use Terlicko\Web\Value\Content\Data\UredniDeskaData;

#[AsTwigComponent]
readonly final class UredniDeska
{
    public function __construct(
        private StrapiContent $content,
    ) {
    }

    /**
     * @param array<KategorieUredniDeskyData> $kategorie
     * @return array<UredniDeskaData>
     */
    public function getItems(int $Pocet, array $kategorie): array
    {
        $categorySlugs = [];
        foreach ($kategorie as $category) {
            $categorySlugs[] = $category->slug;
        }

        return $this->content->getUredniDeskyData(category: $categorySlugs, limit: $Pocet, shouldHideIfExpired: true);
    }
}
