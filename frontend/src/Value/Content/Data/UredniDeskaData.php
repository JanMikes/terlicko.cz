<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;

final class UredniDeskaData
{
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public readonly int|null $id,
        public readonly string $Nadpis,
        public readonly DateTimeImmutable $Datum_zverejneni,
        public readonly DateTimeImmutable|null $Datum_stazeni,

        /**
         * @var array<FileData> $Soubory
         */
        public readonly array $Soubory,
        public readonly string|null $Popis,
        public readonly ClovekData|null $Zodpovedna_osoba,

        /**
         * @var array<KategorieUredniDesky> $Kategorie
         */
        public readonly array $Kategorie,

        public readonly null|string $slug,
    )
    {
    }

    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        $kategorie = [];

        foreach (UredniDeskaKategorieField::cases() as $uredniDeskaField) {
            if (isset($data[$uredniDeskaField->name]) && $data[$uredniDeskaField->name] === true) {
                $kategorie[] = $uredniDeskaField->toKategorie();
            }
        }

        return new self(
            $id,
            $data['Nadpis'],
            DateTimeImmutable::createFromFormat('Y-m-d', $data['Datum_zverejneni']),
            $data['Datum_stazeni'] ? DateTimeImmutable::createFromFormat('Y-m-d', $data['Datum_stazeni']) : null,
            $data['Soubory']['data'] ? FileData::createManyFromStrapiResponse($data['Soubory']) : [],
            $data['Popis'],
            $data['Zodpovedna_osoba']['data'] ? ClovekData::createFromStrapiResponse($data['Zodpovedna_osoba']['data']['attributes']) : null,
            $kategorie,
            $data['slug'],
        );
    }
}
