<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Strapi;

use Psr\Clock\ClockInterface;
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

/**
 * @phpstan-import-type AktualitaDataArray from AktualitaData
 * @phpstan-import-type ClovekDataArray from ClovekData
 * @phpstan-import-type DlazdiceDataArray from DlazdiceData
 * @phpstan-import-type FileDataArray from FileData
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type MenuDataArray from MenuData
 * @phpstan-import-type SekceDataArray from SekceData
 * @phpstan-import-type TagDataArray from TagData
 * @phpstan-import-type UredniDeskaDataArray from UredniDeskaData
 */
readonly final class StrapiContent
{
    public function __construct(
        private StrapiApiClient $strapiClient,
        private ClockInterface $clock,
    ) {}

    /**
     * @return array<string, SekceData>
     */
    public function getSectionSlugs(): array
    {
        /** @var array{data: array<SekceDataArray>} $strapiResponse */
        $strapiResponse = $this->strapiClient->getApiResource('sekces', populateLevel: 2);

        $data = [];

        foreach ($strapiResponse['data'] as $sekceData) {
            $data[$sekceData['slug']] = SekceData::createFromStrapiResponseWithoutComponents($sekceData);
        }

        return $data;
    }

    /**
     * @param null|string|array<string> $tag
     * @return array<AktualitaData>
     */
    public function getAktualityData(int|null $limit = null, null|array|string $tag = null): array
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

        ];

        if (is_string($tag)) {
           $filters['tags'] = ['slug' => ['$eq' => $tag]];
        }

        if (is_array($tag)) {
            foreach ($tag as $tagName) {
                $filters['tags']['slug']['$in'][] = $tagName;
            }
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
        $now = $this->clock->now();
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
     * @return array<UredniDeskaData>
     */
    public function getUredniDeskyDataFilteredByKategorie(string $kategorieSlug): array
    {
        // TODO
        return [];
        // return $this->getUredniDeskyData($field, shouldHideIfExpired: true);
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
