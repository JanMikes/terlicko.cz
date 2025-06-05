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
        public null|ImageData $large = null,
        public null|ImageData $thumbnail = null,
    ) {
    }


    /**
     * @param ImageDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        /** @var numeric-string $size */
        $size = $data['size'];

        $large = null;
        $thumbnail = null;

        if (isset($data['formats']['large'])) {
            /** @var numeric-string $largeSize */
            $largeSize = $data['formats']['large']['size'];

            $large = new ImageData(
                name: $data['formats']['large']['name'],
                caption: $data['caption'],
                url: $data['formats']['large']['url'],
                size: (int) $largeSize,
                ext: trim($data['formats']['large']['ext'], '.'),
            );
        }

        if (isset($data['formats']['thumbnail'])) {
            /** @var numeric-string $thumbnailSize */
            $thumbnailSize = $data['formats']['thumbnail']['size'];

            $thumbnail = new ImageData(
                name: $data['formats']['large']['name'],
                caption: $data['caption'],
                url: $data['formats']['large']['url'],
                size: (int) $thumbnailSize,
                ext: trim($data['formats']['large']['ext'], '.'),
            );
        }

        return new self(
            name: $data['name'],
            caption: $data['caption'],
            url: $data['url'],
            size: (int) $size,
            ext: trim($data['ext'], '.'),
            large: $large,
            thumbnail: $thumbnail,
        );
    }
}
