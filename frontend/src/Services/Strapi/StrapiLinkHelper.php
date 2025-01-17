<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Strapi;

use Terlicko\Web\Value\Content\Data\SekceData;

final class StrapiLinkHelper
{
    /** @var null|array<string, SekceData> */
    private null|array $sections = null;

    public function __construct(
        readonly private StrapiContent $strapiContent
    ) {
    }

    public function getLinkForSlug(string $slug): string
    {
        $sections = $this->getSections();
        $path = [];
        $currentSlug = $slug;

        while ($currentSlug !== null) {
            $section = $sections[$currentSlug] ?? null;

            if ($section === null) {
                return '#';
            }

            // Check for circular reference where parentSlug equals currentSlug
            if ($section->parentSlug === $currentSlug) {
                return '/' . $currentSlug;
            }

            // Prepend the current slug to the path
            array_unshift($path, $currentSlug);

            // Move to the parent slug
            $currentSlug = $section->parentSlug;
        }

        return '/' . implode('/', $path);
    }

    /**
     * @return array<string, SekceData>
     */
    public function getSections(): array
    {
        if ($this->sections === null) {
            $this->sections = $this->strapiContent->getSectionSlugs();
        }

        return $this->sections;
    }
}
