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
final class FileData
{
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public readonly string $name,
        public readonly null|string $caption,
        public readonly string $url,
        public readonly int $size,
        public readonly string $ext,
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
