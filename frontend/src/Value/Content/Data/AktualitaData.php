<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type FileDataArray from FileData
 * @phpstan-import-type TagDataArray from TagData
 * @phpstan-import-type ClovekDataArray from ClovekData
 * @phpstan-type AktualitaDataArray array{
 *      Nadpis: string,
 *      Datum_zverejneni: string,
 *      Video_youtube: null|string,
 *      Popis: null|string,
 *      Zobrazovat: bool,
 *      slug: string,
 *      Zobrazovat_na_uredni_desce: bool,
 *      Obrazek: null|ImageDataArray,
 *      Galerie: null|array<ImageDataArray>,
 *      Zverejnil: null|ClovekDataArray,
 *      tags: array<TagDataArray>,
 *      Soubory: null|array<FileDataArray>,
 *  }
 */
readonly final class AktualitaData
{
    /** @use CanCreateManyFromStrapiResponse<AktualitaDataArray> */
    use CanCreateManyFromStrapiResponse;

    /**
     * @param array<ImageData> $Galerie
     * @param array<TagData> $Tagy
     * @param array<FileData> $Soubory
     */
    public function __construct(
        public string $Nadpis,
        public DateTimeImmutable $DatumZverejneni,
        public null|ImageData $Obrazek,
        public string|null $Video_youtube,
        public array $Galerie,
        public ClovekData|null $Zverejnil,
        public array $Tagy,
        public string $Popis,
        public null|string $slug,
        public array $Soubory,
    ) {}

    /**
     * @param AktualitaDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        $datumZverejneni = new DateTimeImmutable($data['Datum_zverejneni']);
        $tags = TagData::createManyFromStrapiResponse($data['tags']);
        $zverejnil = $data['Zverejnil'] !== null ? ClovekData::createFromStrapiResponse($data['Zverejnil']) : null;
        $soubory = FileData::createManyFromStrapiResponse($data['Soubory'] ?? []);
        $galerie = ImageData::createManyFromStrapiResponse($data['Galerie'] ?? []);
        $obrazek = $data['Obrazek'] !== null ? ImageData::createFromStrapiResponse($data['Obrazek']) : null;

        return new self(
            $data['Nadpis'],
            $datumZverejneni,
            $obrazek,
            $data['Video_youtube'],
            $galerie,
            $zverejnil,
            $tags,
            $data['Popis'] ?? '',
            $data['slug'],
            $soubory,
        );
    }
}
