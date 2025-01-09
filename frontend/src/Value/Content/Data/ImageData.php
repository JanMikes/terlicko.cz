<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class ImageData
{
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public readonly string $name,
        public readonly null|string $caption,
        public readonly string $url,
        public readonly float $size,
        public readonly string $ext,
    )
    {
    }


    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        /** @var numeric-string $size */
        $size = $data['size'];

        return new self(
            $data['name'],
            $data['caption'],
            $data['url'],
            (int) $size,
            trim($data['ext'], '.'),
        );
    }
}
