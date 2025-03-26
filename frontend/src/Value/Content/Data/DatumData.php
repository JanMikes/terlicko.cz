<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;

/**
 * @phpstan-type DatumDataArray array{
 *     Datum: string,
 *  }
 */
readonly final class DatumData
{
    /** @use CanCreateManyFromStrapiResponse<DatumDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public DateTimeImmutable $Datum,
    ) {}

    /**
     * @param DatumDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Datum: new DateTimeImmutable($data['Datum']),
        );
    }
}
