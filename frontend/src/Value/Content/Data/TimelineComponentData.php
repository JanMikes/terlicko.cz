<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type TimelineDataArray from TimelineData
 * @phpstan-type TimelineDataArray array{
 *     Polozky: <TimelineDataArray>
 *  }
 */
readonly final class TimelineComponentData
{
    /** @use CanCreateManyFromStrapiResponse<TimelineDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public array $Polozky,
    ) {}

    /**
     * @param TimelineDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Polozky: TimelineData::createManyFromStrapiResponse($data['Polozky']),
        );
    }
}
