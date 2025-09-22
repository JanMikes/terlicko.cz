<?php

declare(strict_types=1);

namespace Terlicko\Web\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Terlicko\Web\Services\Strapi\StrapiContent;
use Terlicko\Web\Value\Content\Data\KategorieUredniDeskyData;
use Terlicko\Web\Value\Content\Data\TagData;
use Terlicko\Web\Value\Content\Data\UredniDeskaData;

#[AsTwigComponent]
final class UredniDeska
{
    /** @var array<string, array<UredniDeskaData>> */
    private array $cache = [];

    public function __construct(
        private readonly StrapiContent $content,
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

        $hideExpired = true;

        if (count($kategorie) > 0) {
            $hideExpired = false;
        }

        $cacheKey = md5(serialize([$Pocet, $categorySlugs, $hideExpired]));

        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->content->getUredniDeskyData(category: $categorySlugs, limit: $Pocet, shouldHideIfExpired: $hideExpired);
        }

        return $this->cache[$cacheKey];
    }
}
