<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-type KartaDataArray array{
 *     id: int,
 *     Nazev: string,
 *     Adresa: null|string,
 *     Telefon: null|string,
 *     Email: null|string,
 *     Odkaz: null|string,
 *     Odkaz_na_mapu: null|string,
 *     Obrazek: null|ImageDataArray,
 *  }
 */
readonly final class KartaData
{
    /** @use CanCreateManyFromStrapiResponse<KartaDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public int $id,
        public string $Nazev,
        public null|string $Adresa,
        public null|string $Telefon,
        public null|string $Email,
        public null|string $Odkaz,
        public null|string $OdkazNaMapu,
        public null|ImageData $Obrazek,
    ) {}

    /**
     * @param KartaDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            id: $data['id'],
            Nazev: $data['Nazev'],
            Adresa: $data['Adresa'],
            Telefon: $data['Telefon'],
            Email: $data['Email'],
            Odkaz: $data['Odkaz'],
            OdkazNaMapu: $data['Odkaz_na_mapu'],
            Obrazek: $data['Obrazek'] !== null ? ImageData::createFromStrapiResponse($data['Obrazek']) : null,
        );
    }
}
