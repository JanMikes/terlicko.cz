<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type TelefonDataArray from TelefonData
 * @phpstan-import-type LekarDataArray from LekarData
 * @phpstan-type VizitkaDataArray array{
 *     Adresa: null|string,
 *     Odkaz_na_mapu: null|string,
 *     Odkaz: null|string,
 *     Oteviraci_doba: null|string,
 *     Telefony: array<TelefonDataArray>,
 *     Lekari: array<LekarDataArray>,
 * }
 */
readonly final class VizitkaData
{
    /** @use CanCreateManyFromStrapiResponse<VizitkaDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public null|string $Adresa,
        public null|string $OdkazNaMapu,
        public null|string $Odkaz,
        public null|string $OteviraciDoba,
        /** @var array<LekarData> */
        public array $Lekari,
        /** @var array<TelefonData> */
        public array $Telefony,
    ) {}

    /**
     * @param VizitkaDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Adresa: $data['Adresa'],
            OdkazNaMapu: $data['Odkaz_na_mapu'],
            Odkaz: $data['Odkaz'],
            OteviraciDoba: $data['Oteviraci_doba'],
            Lekari: LekarData::createManyFromStrapiResponse($data['Lekari']),
            Telefony: TelefonData::createManyFromStrapiResponse($data['Telefony']),
        );
    }
}
