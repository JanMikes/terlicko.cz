<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type TimelineDataArray from TimelineData
 * @phpstan-type TimelineComponentDataArray array{
 *     Polozky: array<TimelineDataArray>,
 * }
 */
readonly final class TimelineComponentData
{
    public function __construct(
        /** @var array<TimelineData> */
        public array $Polozky,
    ) {}

    /**
     * @param TimelineComponentDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Polozky: TimelineData::createManyFromStrapiResponse($data['Polozky']),
        );
    }
}
