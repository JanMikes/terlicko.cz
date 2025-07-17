<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type TlacitkoDataArray from TlacitkoData
 * @phpstan-type TimelineDataArray array{
 *     Nadpis: null|string,
 *     Text: null|string,
 *     Fotka: null|ImageDataArray,
 *     Tlacitko: null|TlacitkoDataArray,
 *  }
 */
readonly final class TimelineData
{
    /** @use CanCreateManyFromStrapiResponse<TimelineDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public null|string $Nadpis,
        public null|string $Text,
        public null|ImageData $Fotka,
        public null|TlacitkoData $Tlacitko,
    ) {}

    /**
     * @param TimelineDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Nadpis: $data['Nadpis'],
            Text: $data['Text'],
            Fotka: $data['Fotka'] !== null ? ImageData::createFromStrapiResponse($data['Fotka']) : null,
            Tlacitko: $data['Tlacitko'] !== null ? TlacitkoData::createFromStrapiResponse($data['Tlacitko']) : null,
        );
    }
}
