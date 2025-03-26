<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

readonly final class KartyComponentData
{
    public function __construct(
    ) {}

    /**
     * @param array<mixed> $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self;
    }
}
