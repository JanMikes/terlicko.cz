<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type FileDataArray from FileData
 * @phpstan-import-type TagDataArray from TagData
 * @phpstan-import-type ClovekDataArray from ClovekData
 */
final class AktualitaData
{
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public readonly string $Nadpis,
        public readonly DateTimeImmutable $DatumZverejneni,
        public readonly null|string $Obrazek,
        public readonly string|null $Video_youtube,

        /** @var array<string> $Galerie */
        public readonly array $Galerie,

        public readonly ClovekData|null $Zverejnil,

        /** @var array<string, TagData> $Tagy*/
        public readonly array $Tagy,

        public readonly string $Popis,

        public readonly null|string $slug,

        /** @var array<FileData> $Soubory */
        public readonly array $Soubory,
    ) {}


    /**
     * @param array{
     *     Nadpis: string,
     *     Datum_zverejneni: string,
     *     Video_youtube: string,
     *     Popis: string,
     *     Zobrazovat: bool,
     *     slug: string,
     *     Zobrazovat_na_uredni_desce: bool,
     *     Obrazek: ImageDataArray,
     *     Galerie: array<ImageDataArray>,
     *     Zverejnil: ClovekDataArray,
     *     Tagy: array<TagDataArray>,
     *     Soubory: array<FileDataArray>,
     * } $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        $tags = [];
        foreach ($data['Tagy'] as $tagData) {
            $tags[$tagData['slug']] = TagData::createFromStrapiResponse($tagData);
        }

        $datumZverejneni = new DateTimeImmutable($data['Datum_zverejneni']);
        $zverejnil = $data['Zverejnil'] ? ClovekData::createFromStrapiResponse($data['Zverejnil']) : null;

        $galerie = array_map(
            callback: fn(array $item): string => $item['url'],
            array: $data['Galerie'],
        );

        $soubory = isset($data['Soubory']) ? FileData::createManyFromStrapiResponse($data['Soubory']) : [];

        return new self(
            $data['Nadpis'],
            $datumZverejneni,
            $data['Obrazek']['url'] ?? null,
            $data['Video_youtube'],
            $galerie,
            $zverejnil,
            $tags,
            $data['Popis'],
            $data['slug'],
            $soubory,
        );
    }
}
