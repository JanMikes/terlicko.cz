<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;

/**
 * @phpstan-import-type KategorieUredniDeskyDataArray from KategorieUredniDeskyData
 * @phpstan-import-type FileDataArray from FileData
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type ClovekDataArray from ClovekData
 * @phpstan-type UredniDeskaDataArray array{
 *     Nadpis: string,
 *     Datum_zverejneni: string,
 *     Datum_stazeni: null|string,
 *     Popis: null|string,
 *     Zodpovedna_osoba: null|ClovekDataArray,
 *     Soubory: null|array<FileDataArray>,
 *     slug: string,
 *     Ikonka: null|ImageData,
 *     Uvodni_obrazek: null|ImageData,
 *     categories: array<KategorieUredniDeskyDataArray>
 *  }
 */
readonly final class UredniDeskaData
{
    /** @use CanCreateManyFromStrapiResponse<UredniDeskaDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $Nadpis,
        public DateTimeImmutable $Datum_zverejneni,
        public DateTimeImmutable|null $Datum_stazeni,
        /** @var array<FileData> */
        public array $Soubory,
        public null|string $Popis,
        public null|ClovekData $Zodpovedna_osoba,
        /** @var array<KategorieUredniDeskyData> */
        public array $Kategorie,
        public null|string $slug,
    ) {
    }

    /**
     * @param UredniDeskaDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['Nadpis'],
            new DateTimeImmutable($data['Datum_zverejneni']),
            $data['Datum_stazeni'] ? new DateTimeImmutable($data['Datum_stazeni']) : null,
            FileData::createManyFromStrapiResponse($data['Soubory'] ?? []),
            $data['Popis'],
            $data['Zodpovedna_osoba'] !== null ? ClovekData::createFromStrapiResponse($data['Zodpovedna_osoba']) : null,
            KategorieUredniDeskyData::createManyFromStrapiResponse($data['categories']),
            $data['slug'],
        );
    }
}
