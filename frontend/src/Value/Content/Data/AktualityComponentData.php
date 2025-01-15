<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type TagDataArray from TagData
 */
readonly final class AktualityComponentData
{
    /**
     * @param array<TagData> $kategorie
     */
    public function __construct(
        public int $Pocet,
        public array $kategorie,
    ) {}

    /**
     * @param array{
     *     Pocet: int,
     *     kategories: array<TagDataArray>,
     * } $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['Pocet'],
            TagData::createManyFromStrapiResponse($data['kategories']),
        );
    }
}
