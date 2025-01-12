<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Strapi;

use Terlicko\Web\Value\Content\Data\AktualitaData;
use Terlicko\Web\Value\Content\Data\KategorieUredniDesky;
use Terlicko\Web\Value\Content\Data\MenuData;
use Terlicko\Web\Value\Content\Data\SekceData;
use Terlicko\Web\Value\Content\Data\UredniDeskaData;
use Terlicko\Web\Value\Content\Exception\InvalidKategorie;
use Terlicko\Web\Value\Content\Exception\NotFound;
use Terlicko\Web\Value\Content\Data\ClovekData;
use Terlicko\Web\Value\Content\Data\DlazdiceData;
use Terlicko\Web\Value\Content\Data\FileData;
use Terlicko\Web\Value\Content\Data\ImageData;
use Terlicko\Web\Value\Content\Data\TagData;
use Terlicko\Web\Value\Content\Data\TlacitkoData;

/**
 * @phpstan-import-type AktualitaDataArray from AktualitaData
 * @phpstan-import-type ClovekDataArray from ClovekData
 * @phpstan-import-type DlazdiceDataArray from DlazdiceData
 * @phpstan-import-type FileDataArray from FileData
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type MenuDataArray from MenuData
 * @phpstan-import-type SekceDataArray from SekceData
 * @phpstan-import-type TagDataArray from TagData
 * @phpstan-import-type TlacitkoDataArray from TlacitkoData
 * @phpstan-import-type UredniDeskaDataArray from UredniDeskaData
 */
readonly final class StrapiContent
{
    public function __construct(
        private StrapiApiClient $strapiClient,
    ) {}

    /**
     * @return array<AktualitaData>
     */
    public function getAktualityData(int|null $limit = null, null|string $tag = null): array
    {
        $pagination = null;

        if ($limit !== null) {
            $pagination = [
                'limit' => $limit,
                'start' => 0,
            ];
        }

        $filters = [
            'Zobrazovat' => ['$eq' => true],
            'Tagy' => ['slug' => ['$eq' => $tag]],
        ];

        if ($tag == null) {
            unset($filters['Tagy']);
        }

        /** @var array{data: array<AktualitaDataArray>} $strapiResponse */
        $strapiResponse = $this->strapiClient->getApiResource('aktualities',
            filters: $filters,
            pagination: $pagination,
            sort: [
                'Datum_zverejneni:desc'
            ]);

        return AktualitaData::createManyFromStrapiResponse($strapiResponse['data']);
    }

    public function getAktualitaData(string $slug): AktualitaData
    {
        /** @var array{data: array<AktualitaDataArray>} $strapiResponse */
        $strapiResponse = $this->strapiClient->getApiResource('aktualities',
            filters: [
                'Zobrazovat' => ['$eq' => true],
                'slug' => ['$eq' => $slug]
            ]);

        return AktualitaData::createFromStrapiResponse(
            $strapiResponse['data'][0] ?? throw new NotFound
        );
    }

    /**
     * @return array<UredniDeskaData>
     */
    public function getUredniDeskyData(string|null $categoryField = null, int|null $limit = null, bool $shouldHideIfExpired = false): array
    {
        $now = new \DateTimeImmutable();

        $filters = [];

        if ($shouldHideIfExpired === true) {
            $filters = [
                'Zobrazovat' => ['$eq' => true],
                '$or' => [
                    ['Datum_stazeni' => ['$null' => true]],
                    ['Datum_stazeni' => ['$gte' => $now->format('Y-m-d')]],
                ],
            ];
        }

        if ($categoryField) {
            $filters[$categoryField] = ['$eq' => true];
        }

        $pagination = null;

        if ($limit !== null) {
            $pagination = [
                'limit' => $limit,
                'start' => 0,
            ];
        }

        /** @var array{data: array<UredniDeskaDataArray>} $strapiResponse */
        $strapiResponse = $this->strapiClient->getApiResource('uredni-deskas',
            filters: $filters,
            pagination: $pagination,
            sort: ['Datum_zverejneni:desc', 'Nadpis'],
        );

        return UredniDeskaData::createManyFromStrapiResponse($strapiResponse['data']);
    }

    public function getUredniDeskaData(string $slug): UredniDeskaData
    {
        /** @var array{data: array<UredniDeskaDataArray>} $strapiResponse */
        $strapiResponse = $this->strapiClient->getApiResource('uredni-deskas',
            filters: [
                'slug' => ['$eq' => $slug],
            ]);

        return UredniDeskaData::createFromStrapiResponse(
            $strapiResponse['data'][0] ?? throw new NotFound
        );
    }


    /**
     * @throws InvalidKategorie
     *
     * @return array<UredniDeskaData>
     */
    public function getUredniDeskyDataFilteredByKategorie(string $kategorieSlug): array
    {
        $kategorie = KategorieUredniDesky::fromSlug($kategorieSlug);
        $field = $this->uredniDeskaKategorieToUredniDeskaField($kategorie->name);

        return $this->getUredniDeskyData($field, shouldHideIfExpired: true);
    }


    private function uredniDeskaKategorieToUredniDeskaField(string $kategorie): string|null
    {
        return match ($kategorie) {
            'Formulare' => 'Zobrazit_v_formulare',
            'Navody' => 'Zobrazit_v_navody',
            'Odpady' => 'Zobrazit_v_odpady',
            'Rozpocty' => 'Zobrazit_v_rozpocty',
            'Strategicke_dokumenty' => 'Zobrazit_v_strategicke_dokumenty',
            'Uzemni_plan' => 'Zobrazit_v_uzemni_plan',
            'Uzemni_studie' => 'Zobrazit_v_uzemni_studie',
            'Vyhlasky' => 'Zobrazit_v_vyhlasky',
            'Vyrocni_zpravy' => 'Zobrazit_v_vyrocni_zpravy',
            'Zivotni_situace' => 'Zobrazit_v_zivotni_situace',
            'Poskytnute_informace' => 'Zobrazit_v_poskytnute_informace',
            'Verejnopravni_smlouvy' => 'Zobrazit_v_verejnopravni_smlouvy',
            'Zapisy_z_jednani_zastupitelstva' => 'Zobrazit_v_zapisy_z_jednani_zastupitelstva',
            'Usneseni_rady' => 'Zobrazit_v_usneseni_rady',
            'Financni_vybor' => 'Zobrazit_v_financni_vybor',
            'Kulturni_komise' => 'Zobrazit_v_kulturni_komise',
            'Volby' => 'Zobrazit_v_volby',
            'Projekty' => 'Zobrazit_v_projekty',
            '-' => null,
            default => throw new \LogicException('Resource not matched: ' . $kategorie),
        };
    }


    /**
     * @return array<TagData>
     */
    public function getTagy(): array
    {
        /** @var array{data: array<TagDataArray>} $strapiResponse */
        $strapiResponse = $this->strapiClient->getApiResource('tagies');

        return TagData::createManyFromStrapiResponse($strapiResponse['data']);
    }

    /**
     * @return array<MenuData>
     */
    public function getMenu(): array
    {
        /** @var array{data: array<MenuDataArray>} $strapiResponse */
        $strapiResponse = $this->strapiClient->getApiResource('menus', sort: ['Poradi']);

        return MenuData::createManyFromStrapiResponse($strapiResponse['data']);
    }

    public function getSekceData(string $slug): SekceData
    {
        /** @var array{data: array<SekceDataArray>} $strapiResponse */
        $strapiResponse = $this->strapiClient->getApiResource('sekces',
            populateLevel: 6,
            filters: [
            'slug' => ['$eq' => $slug]
        ]);

        return SekceData::createFromStrapiResponse(
            $strapiResponse['data'][0] ?? throw new NotFound
        );
    }
}
