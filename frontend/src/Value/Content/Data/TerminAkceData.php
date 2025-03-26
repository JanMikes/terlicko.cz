<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;

/**
 * @phpstan-type TerminAkceDataArray array{
 *     id: int,
 *     Termin: string,
 *     Zivy_prenos: null|string,
 *     Zaznam: null|string,
 *  }
 */
readonly final class TerminAkceData
{
    public function __construct(
        public int $id,
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
            id: $data['id'],
            Termin: $data['Termin'] ? new DateTimeImmutable($data['Termin']) : null,
            ZivyPrenos: $data['Zivy_prenos'],
            Zaznam: $data['Zaznam'],
        );
    }
}
