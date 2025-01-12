<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

readonly final class TextovePoleComponentData
{
    public function __construct(
        public string $Text,
    ) {}

    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['Text'],
        );
    }
}
