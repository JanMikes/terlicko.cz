<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;

/**
 * @phpstan-type PoleFormulareSMoznostmiDataArray array{
 *      Povinne: bool,
 *      Typ: string,
 *      Nadpis_pole: string,
 *  }
 */
readonly final class PoleFormulareSMoznostmiData
{
    /** @use CanCreateManyFromStrapiResponse<PoleFormulareSMoznostmiDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public bool $Povinne,
        public string $Typ,
        public string $Nadpis_pole,
    ) {}

    /**
     * @param PoleFormulareSMoznostmiDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['Povinne'],
            $data['Typ'],
            $data['Nadpis_pole'],
        );
    }
}
