<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type FileDataArray array{
 *     name: string,
 *     caption: null|string,
 *     url: string,
 *     size: int,
 *     ext: string,
 *   }
 */
readonly final class FileData
{
    /** @use CanCreateManyFromStrapiResponse<FileDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $name,
        public null|string $caption,
        public string $url,
        public int $size,
        public string $ext,
    ) {}

    /**
     * @param FileDataArray $data
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
