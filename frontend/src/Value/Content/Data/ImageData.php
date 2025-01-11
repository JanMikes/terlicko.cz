<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type ImageDataArray array{
 *     name: string,
 *     caption: null|string,
 *     size: float,
 *     url: string,
 *     ext: string,
 *     formats: array<array{
 *      name: string,
 *      caption: null|string,
 *      size: float,
 *      url: string,
 *      ext: string,
 *     }>,
 *   }
 */
readonly final class ImageData
{
    /** @use CanCreateManyFromStrapiResponse<ImageDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $name,
        public null|string $caption,
        public string $url,
        public float $size,
        public string $ext,
    ) {
    }


    /**
     * @param ImageDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
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
