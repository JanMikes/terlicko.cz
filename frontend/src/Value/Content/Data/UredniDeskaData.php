<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;

/**
 * @phpstan-import-type FileDataArray from FileData
 * @phpstan-import-type ClovekDataArray from ClovekData
 * @phpstan-type UredniDeskaDataArray array{
 *      Zobrazit_v_formulare: bool,
 *      Zobrazit_v_navody: bool,
 *      Zobrazit_v_odpady: bool,
 *      Zobrazit_v_rozpocty: bool,
 *      Zobrazit_v_strategicke_dokumenty: bool,
 *      Zobrazit_v_uzemni_plan: bool,
 *      Zobrazit_v_uzemni_studie: bool,
 *      Zobrazit_v_vyhlasky: bool,
 *      Zobrazit_v_vyrocni_zpravy: bool,
 *      Zobrazit_v_zivotni_situace: bool,
 *      Zobrazit_v_poskytnute_informace: bool,
 *      Zobrazit_v_verejnopravni_smlouvy: bool,
 *      Zobrazit_v_usneseni_rady: bool,
 *      Zobrazit_v_financni_vybor: bool,
 *      Zobrazit_v_kulturni_komise: bool,
 *      Zobrazit_v_volby: bool,
 *      Zobrazit_v_projekty: bool,
 *      Nadpis: string,
 *      Datum_zverejneni: string,
 *      Datum_stazeni: null|string,
 *      Popis: null|string,
 *      Zodpovedna_osoba: null|ClovekDataArray,
 *      Soubory: null|array<FileDataArray>,
 *      slug: string,
 *  }
 */
readonly final class UredniDeskaData
{
    /** @use CanCreateManyFromStrapiResponse<UredniDeskaDataArray> */
    use CanCreateManyFromStrapiResponse;

    /**
     * @param array<FileData> $Soubory
     * @param array<KategorieUredniDesky> $Kategorie
     */
    public function __construct(
        public string $Nadpis,
        public DateTimeImmutable $Datum_zverejneni,
        public DateTimeImmutable|null $Datum_stazeni,
        public array $Soubory,
        public null|string $Popis,
        public null|ClovekData $Zodpovedna_osoba,
        public array $Kategorie,
        public null|string $slug,
    ) {
    }

    /**
     * @param UredniDeskaDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        $kategorie = [];

        foreach (UredniDeskaKategorieField::cases() as $uredniDeskaField) {
            if ($data[$uredniDeskaField->name] === true) {
                $kategorie[] = $uredniDeskaField->toKategorie();
            }
        }

        return new self(
            $data['Nadpis'],
            new DateTimeImmutable($data['Datum_zverejneni']),
            $data['Datum_stazeni'] ? new DateTimeImmutable($data['Datum_stazeni']) : null,
            FileData::createManyFromStrapiResponse($data['Soubory'] ?? []),
            $data['Popis'],
            $data['Zodpovedna_osoba'] !== null ? ClovekData::createFromStrapiResponse($data['Zodpovedna_osoba']) : null,
            $kategorie,
            $data['slug'],
        );
    }
}
