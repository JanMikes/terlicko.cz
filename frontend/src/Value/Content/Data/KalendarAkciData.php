<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;
use DateTimeZone;

/**
 * @phpstan-import-type AktualitaDataArray from AktualitaData
 * @phpstan-type KalendarAkciDataArray array{
 *     Datum: null|string,
 *     Nazev: null|string,
 *     Poradatel: null|string,
 *     Aktualita: null|AktualitaDataArray,
 * }
 */
readonly final class KalendarAkciData
{
    /** @use CanCreateManyFromStrapiResponse<KalendarAkciDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public null|DateTimeImmutable $Datum,
        public null|string $Nazev,
        public null|string $Poradatel,
        public null|AktualitaData $Aktualita,
    ) {
    }

    /**
     * @param KalendarAkciDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        $datum = null;

        if ($data['Datum']) {
            $datum = (new DateTimeImmutable($data['Datum'], new DateTimeZone('UTC')))
                ->setTimezone(new DateTimeZone('Europe/Prague'));
        }

        return new self(
            Datum: $datum,
            Nazev: $data['Nazev'],
            Poradatel: $data['Poradatel'],
            Aktualita: $data['Aktualita'] !== null ? AktualitaData::createFromStrapiResponse($data['Aktualita']) : null,
        );
    }
}
