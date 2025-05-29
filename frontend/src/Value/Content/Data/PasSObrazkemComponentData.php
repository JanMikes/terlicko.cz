<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type SouborDataArray from SouborData
 * @phpstan-import-type TlacitkoDataArray from TlacitkoData
 */
readonly final class PasSObrazkemComponentData
{
    public function __construct(
        public null|string $Umisteni_fotky,
        public null|string $Nadpis,
        public null|string $Text,
        public null|ImageData $Fotka,
        /** @var array<TlacitkoData> */
        public array $Tlacitka,
        public null|ImageData $Pozadi,
        public null|string $Pozadi_barva,
    ) {}

    /**
     * @param array{
     *     Umisteni_fotky: null|string,
     *     Nadpis: null|string,
     *     Text: null|string,
     *     Fotka: null|ImageDataArray,
     *     Tlacitko: null|array<TlacitkoDataArray>,
     *     Pozadi: null|ImageDataArray,
     *     Pozadi_barva: null|string,
     *  } $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Umisteni_fotky: $data['Umisteni_fotky'],
            Nadpis: $data['Nadpis'],
            Text: $data['Text'],
            Fotka: $data['Fotka'] !== null ? ImageData::createFromStrapiResponse($data['Fotka']) : null,
            Tlacitka: TlacitkoData::createManyFromStrapiResponse($data['Tlacitko'] ?? []),
            Pozadi: $data['Pozadi'] !== null ? ImageData::createFromStrapiResponse($data['Pozadi']) : null,
            Pozadi_barva: $data['Pozadi_barva'],
        );
    }
}
