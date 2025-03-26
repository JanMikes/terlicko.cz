<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type LekarDataArray array{
 *      Jmeno: string,
 *  }
 */
readonly final class LekarData
{
    /** @use CanCreateManyFromStrapiResponse<LekarDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $Jmeno,
    ) {}

    /**
     * @param LekarDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Jmeno: $data['Jmeno'],
        );
    }
}
