<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class FileData
{
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public readonly string $name,
        public readonly string $caption,
        public readonly string $url,
        public readonly int $kbytes,
        public readonly string $ext,
    ) {}


    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        $data = $data['data']['attributes'] ?? $data;

        return new self(
            $data['name'],
            $data['caption'],
            $data['url'],
            (int) $data['size'],
            trim($data['ext'], '.'),
        );
    }
}
