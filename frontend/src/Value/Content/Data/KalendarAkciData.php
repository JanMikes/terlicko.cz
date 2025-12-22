<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;
use DateTimeZone;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type GalerieComponentDataArray from GalerieComponentData
 * @phpstan-import-type SouborDataArray from SouboryKeStazeniComponentData
 * @phpstan-import-type FileDataArray from FileData
 * @phpstan-import-type TagDataArray from TagData
 * @phpstan-type KalendarAkciDataArray array{
 *     Datum: null|string,
 *     Datum_do: null|string,
 *     Nazev: null|string,
 *     slug: null|string,
 *     Poradatel: null|string,
 *     Misto_konani: null|string,
 *     Popis: null|string,
 *     Fotka_detail: null|ImageDataArray,
 *     Video_youtube: null|string,
 *     Galerie: null|GalerieComponentDataArray,
 *     Dokumenty: null|SouborDataArray,
 * }
 */
readonly final class KalendarAkciData
{
    /** @use CanCreateManyFromStrapiResponse<KalendarAkciDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public null|DateTimeImmutable $Datum,
        public null|DateTimeImmutable $DatumDo,
        public null|string $Nazev,
        public null|string $slug,
        public null|string $Poradatel,
        public null|string $Popis,
        public null|string $MistoKonani,
        public null|ImageData $FotkaDetail,
        public string|null $VideoYoutube,
        public null|GalerieComponentData $Galerie,
        public null|SouboryKeStazeniComponentData $Dokumenty,
    ) {
    }

    /**
     * @param KalendarAkciDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        $datum = null;
        $datumDo = null;

        if ($data['Datum']) {
            $datum = (new DateTimeImmutable($data['Datum'], new DateTimeZone('UTC')))
                ->setTimezone(new DateTimeZone('Europe/Prague'));
        }

        if ($data['Datum_do']) {
            $datumDo = (new DateTimeImmutable($data['Datum_do'], new DateTimeZone('UTC')))
                ->setTimezone(new DateTimeZone('Europe/Prague'));
        }

        $gallery = null;
        if ($data['Galerie'] !== null) {
            $gallery = GalerieComponentData::createFromStrapiResponse($data['Galerie']);
        }

        $documents = null;
        if ($data['Dokumenty'] !== null) {
            $documents = SouboryKeStazeniComponentData::createFromStrapiResponse($data['Dokumenty']);
        }

        return new self(
            Datum: $datum,
            DatumDo: $datumDo,
            Nazev: $data['Nazev'],
            slug: $data['slug'],
            Poradatel: $data['Poradatel'],
            Popis: $data['Popis'],
            MistoKonani: $data['Misto_konani'],
            FotkaDetail: $data['Fotka_detail'] !== null ? ImageData::createFromStrapiResponse($data['Fotka_detail']) : null,
            VideoYoutube: $data['Video_youtube'],
            Galerie: $gallery,
            Dokumenty: $documents,
        );
    }
}
