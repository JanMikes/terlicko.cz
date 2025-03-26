<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;

/**
 * @phpstan-type TerminAkceDataArray array{
 *     Termin: string,
 *     Zivy_prenos: null|string,
 *     Zaznam: null|string,
 *  }
 */
readonly final class TerminAkceData
{
    /** @use CanCreateManyFromStrapiResponse<TerminAkceDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public null|DateTimeImmutable $Termin,
        public null|string $ZivyPrenos,
        public null|string $Zaznam,
    ) {}

    /**
     * @param TerminAkceDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Termin: $data['Termin'] ? new DateTimeImmutable($data['Termin']) : null,
            ZivyPrenos: $data['Zivy_prenos'],
            Zaznam: $data['Zaznam'],
        );
    }
}
