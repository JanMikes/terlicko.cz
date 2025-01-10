<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type TagDataArray array{slug: string, Tag: string}
 */
readonly final class TagData
{
    public function __construct(
        public string $slug,
        public string $Tag,
    ) {
    }

    /**
     * @param TagDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            slug: $data['slug'],
            Tag: $data['Tag'],
        );
    }
}
