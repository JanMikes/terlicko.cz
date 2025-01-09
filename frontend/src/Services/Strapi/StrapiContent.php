<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Strapi;

use Terlicko\Web\Value\Content\Content;
use Terlicko\Web\Value\Content\Data\AktualitaData;
use Terlicko\Web\Value\Content\Data\BannerSTextemData;
use Terlicko\Web\Value\Content\Data\BannerSTlacitkamaData;
use Terlicko\Web\Value\Content\Data\FooterData;
use Terlicko\Web\Value\Content\Data\GdprData;
use Terlicko\Web\Value\Content\Data\DokumentyData;
use Terlicko\Web\Value\Content\Data\GrafickyPasData;
use Terlicko\Web\Value\Content\Data\KartaObjektuData;
use Terlicko\Web\Value\Content\Data\KategorieUredniDesky;
use Terlicko\Web\Value\Content\Data\KontaktyData;
use Terlicko\Web\Value\Content\Data\MenuData;
use Terlicko\Web\Value\Content\Data\ObecData;
use Terlicko\Web\Value\Content\Data\PristupnostData;
use Terlicko\Web\Value\Content\Data\RestauraceData;
use Terlicko\Web\Value\Content\Data\SamospravaData;
use Terlicko\Web\Value\Content\Data\SekceSDlazdicemaData;
use Terlicko\Web\Value\Content\Data\SluzbaData;
use Terlicko\Web\Value\Content\Data\SluzbyData;
use Terlicko\Web\Value\Content\Data\StrukturaUraduData;
use Terlicko\Web\Value\Content\Data\TlacitkoData;
use Terlicko\Web\Value\Content\Data\UbytovaniData;
use Terlicko\Web\Value\Content\Data\UradData;
use Terlicko\Web\Value\Content\Data\UredniDeskaData;
use Terlicko\Web\Value\Content\Data\UzemniData;
use Terlicko\Web\Value\Content\Exception\InvalidKategorie;
use Terlicko\Web\Value\Content\Exception\NotFound;
use Symfony\Component\HttpClient\Exception\ClientException;

final class StrapiContent
{
    public function __construct(
        private StrapiApiClient $strapiClient,
    ) {}


    public function getRestauraceData(): RestauraceData
    {
        $strapiResponse = $this->strapiClient->getApiResource('obec-restaurace', [
            'Banner.Obrazek',
            'Restaurace.Obrazek',
        ]);

        return new RestauraceData(
            BannerSTextemData::createFromStrapiResponse($strapiResponse['data']['attributes']['Banner']),
            KartaObjektuData::createManyFromStrapiResponse($strapiResponse['data']['attributes']['Restaurace']),
        );
    }

    /**
     * @return array<GrafickyPasData>
     */
    public function getAktivityData(): array
    {
        return $this->getGrafickePasy('obec-aktivity');
    }


    /**
     * @return array<GrafickyPasData>
     */
    public function getOrganizaceData(): array
    {
        return $this->getGrafickePasy('obec-organizace');
    }


    /**
     * @return array<GrafickyPasData>
     */
    public function getObecniOrganizaceData(): array
    {
        return $this->getGrafickePasy('obec-obecni-organizace');
    }


    public function getSluzbyData(): SluzbyData
    {
        $strapiResponse = $this->strapiClient->getApiResource('obec-sluzby', [
            'Banner.Obrazek',
            'Banner.Tlacitka',
            'Sluzby'
        ]);

        return new SluzbyData(
            new BannerSTlacitkamaData(
                $strapiResponse['data']['attributes']['Banner']['Nadpis'],
                $strapiResponse['data']['attributes']['Banner']['Obrazek']['data']['attributes']['url'],
                TlacitkoData::createManyFromStrapiResponse($strapiResponse['data']['attributes']['Banner']['Tlacitka']),
            ),
            SluzbaData::createManyFromStrapiResponse($strapiResponse['data']['attributes']['Sluzby']),
        );
    }


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

        $filters = ['Zobrazovat' => ['$eq' => true]];

        if ($tag !== null) {
            $filters['Tagy']['slug']['$eq'] = $tag;
        }

        $strapiResponse = $this->strapiClient->getApiResource('aktualities', [
            'Obrazek',
            'Galerie',
            'Zverejnil.Fotka',
            'Tagy',
        ],
        filters: $filters,
        pagination: $pagination,
        sort: [
            'Datum_zverejneni:desc'
        ]);

        return AktualitaData::createManyFromStrapiResponse($strapiResponse['data']);
    }


    /**
     * @return array<GrafickyPasData>
     */
    private function getGrafickePasy(string $resourceName): array
    {
        try {
            $strapiResponse = $this->strapiClient->getApiResource($resourceName, [
                'Graficke_pasy.Tlacitko',
                'Graficke_pasy.Obrazek',
                'Graficke_pasy.Letajici_obrazky.Obrazek',
            ]);
        } catch (ClientException $clientException) {
            if ($clientException->getCode() === 404) {
                return [];
            }

            throw $clientException;
        }

        return GrafickyPasData::createManyFromStrapiResponse($strapiResponse['data']['attributes']['Graficke_pasy']);
    }

    public function getAktualitaData(string $slug): AktualitaData
    {
        $strapiResponse = $this->strapiClient->getApiResource('aktualities', [
            'Obrazek',
            'Galerie',
            'Zverejnil.Fotka',
            'Tagy',
            'Soubory'
        ], filters: [
            'Zobrazovat' => ['$eq' => true],
            'slug' => ['$eq' => $slug]
        ]);

        return AktualitaData::createFromStrapiResponse(
            $strapiResponse['data'][0]['attributes'] ?? throw new NotFound
        );
    }


    public function getGdprData(): GdprData
    {
        $strapiResponse = $this->strapiClient->getApiResource('gdpr');

        return new GdprData(
            $strapiResponse['data']['attributes']['Nadpis'],
            $strapiResponse['data']['attributes']['Obsah'],
        );
    }


    public function getUbytovaniData(): UbytovaniData
    {
        $strapiResponse = $this->strapiClient->getApiResource('obec-ubytovani', [
            'Banner.Obrazek',
            'Ubytovani.Obrazek'
        ]);

        return new UbytovaniData(
            BannerSTextemData::createFromStrapiResponse($strapiResponse['data']['attributes']['Banner']),
            KartaObjektuData::createManyFromStrapiResponse($strapiResponse['data']['attributes']['Ubytovani']),
        );
    }


    public function getVyletyData(): array
    {
        return $this->getGrafickePasy('obec-vylety');
    }


    public function getHistorieData(): array
    {
        return $this->getGrafickePasy('obec-historie');
    }


    public function getPristupnostData(): PristupnostData
    {
        $strapiResponse = $this->strapiClient->getApiResource('prohlaseni-o-pristupnosti');

        return new PristupnostData(
            $strapiResponse['data']['attributes']['Nadpis'],
            $strapiResponse['data']['attributes']['Obsah'],
        );
    }


    public function getPristupnostAppData(): PristupnostData
    {
        $strapiResponse = $this->strapiClient->getApiResource('prohlaseni-o-pristupnosti-app');

        return new PristupnostData(
            $strapiResponse['data']['attributes']['Nadpis'],
            $strapiResponse['data']['attributes']['Obsah'],
        );
    }


    private function getGenericDokumentyData(string $resourceName): DokumentyData
    {
        $strapiResponse = $this->strapiClient->getApiResource($resourceName);
        $uredniDeska = [];

        if ($strapiResponse['data']['attributes']['Zobrazovat_komponentu_uredni_desky'] === true) {
            $field = $this->resourceNameToUredniDeskaField($resourceName);
            $uredniDeska = $this->getUredniDeskyData($field);
        }

        return new DokumentyData(
            $strapiResponse['data']['attributes']['Nadpis'],
            $strapiResponse['data']['attributes']['Obsah'],
            $uredniDeska,
        );
    }


    public function getDokumentyFormulareData(): DokumentyData
    {
        return $this->getGenericDokumentyData('urad-dokumenty-formulare');
    }


    public function getDokumentyNavodyData(): DokumentyData
    {
        return $this->getGenericDokumentyData('urad-dokumenty-navody');
    }


    public function getDokumentyOdpadyData(): DokumentyData
    {
        return $this->getGenericDokumentyData('urad-dokumenty-odpady');
    }


    public function getDokumentyVolbyData(): DokumentyData
    {
        return $this->getGenericDokumentyData('urad-dokumenty-volby');
    }


    public function getDokumentyProjektyData(): DokumentyData
    {
        return $this->getGenericDokumentyData('urad-dokumenty-projekty');
    }


    public function getDokumentyRozpoctyData(): DokumentyData
    {
        return $this->getGenericDokumentyData('urad-dokumenty-rozpocty');
    }


    public function getDokumentyStrategickeDokumentyData(): DokumentyData
    {
        return $this->getGenericDokumentyData('urad-dokumenty-strategicke-dokumenty');
    }


    public function getDokumentyVerejnopravniSmlouvyData(): DokumentyData
    {
        return $this->getGenericDokumentyData('urad-dokumenty-verejnopravni-smlouvy');
    }


    public function getUzemniPlanData(): UzemniData
    {
        return $this->getUzemniDataForResource('urad-dokumenty-uzemni-plan');
    }


    public function getUzemniStudieData(): UzemniData
    {
        return $this->getUzemniDataForResource('urad-dokumenty-uzemni-studie');
    }


    private function getUzemniDataForResource(string $resourceName): UzemniData
    {
        $strapiResponse = $this->strapiClient->getApiResource($resourceName, [
            'Kategorie.Dokumenty.Soubor'
        ]);

        $uredniDeska = [];

        if ($strapiResponse['data']['attributes']['Zobrazovat_komponentu_uredni_desky'] === true) {
            $field = $this->resourceNameToUredniDeskaField($resourceName);
            $uredniDeska = $this->getUredniDeskyData($field);
        }

        return UzemniData::createFromStrapiResponse($strapiResponse['data']['attributes'], $uredniDeska);
    }


    public function getDokumentyVyhlaskyData(): DokumentyData
    {
        return $this->getGenericDokumentyData('urad-dokumenty-vyhlasky');
    }


    public function getDokumentyVyrocniZpravaData(): DokumentyData
    {
        return $this->getGenericDokumentyData('urad-dokumenty-vyrocni-zprava');
    }


    public function getDokumentyZivotniSituaceData(): DokumentyData
    {
        return $this->getGenericDokumentyData('urad-dokumenty-zivotni-situace');
    }


    public function getDokumentyPovinneInformaceData(): DokumentyData
    {
        return $this->getGenericDokumentyData('urad-povinne-zverejnovane-informace');
    }


    /**
     * @return array<SamospravaData>
     */
    public function getSamospravaData(): array
    {
        $strapiResponse = $this->strapiClient->getApiResource('urad-samosprava', [
            'Kategorie_samospravy.Lide.Clovek.Fotka'
        ]);

        // Decorate with uredni deska data
        foreach ($strapiResponse['data']['attributes']['Kategorie_samospravy'] as $i => $kategorieData) {
            $uredniDeska = [];
            $uredniDeskaField = $this->uredniDeskaKategorieToUredniDeskaField($kategorieData['Kategorie_uredni_desky']);

            if ($uredniDeskaField !== null) {
                $uredniDeska = $this->getUredniDeskyData($uredniDeskaField);
            }

            $strapiResponse['data']['attributes']['Kategorie_samospravy'][$i]['Uredni_deska'] = $uredniDeska;
        }

        return SamospravaData::createManyFromStrapiResponse($strapiResponse['data']['attributes']['Kategorie_samospravy']);
    }


    public function getKontaktyData(): KontaktyData
    {
        $strapiResponse = $this->strapiClient->getApiResource('urad-kontakty',[
            'Banner.Obrazek',
            'Vedeni_obce.Clovek.Fotka',
        ]);

        return KontaktyData::createFromStrapiResponse($strapiResponse['data']['attributes']);
    }


    public function getStrukturaUraduData(): StrukturaUraduData
    {
        $strapiResponse = $this->strapiClient->getApiResource('urad-struktura-uradu', [
            'Banner.Obrazek',
            'Struktura_uradu.Clovek.Fotka',
            'Struktura_stavebniho_uradu.Clovek.Fotka',
        ]);

        return StrukturaUraduData::createFromStrapiResponse($strapiResponse['data']['attributes']);
    }


    public function getUradData(): UradData
    {
        $strapiResponse = $this->strapiClient->getApiResource('urad', [
            'Banner.Obrazek',
            'Sekce_s_dlazdicema.Dlazdice.Ikona',
        ]);

        return new UradData(
            BannerSTextemData::createFromStrapiResponse($strapiResponse['data']['attributes']['Banner']),
            SekceSDlazdicemaData::createFromStrapiResponse($strapiResponse['data']['attributes']['Sekce_s_dlazdicema']),
        );
    }


    public function getObecData(): ObecData
    {
        $strapiResponse = $this->strapiClient->getApiResource('obec', [
            'Banner.Obrazek',
            'Sekce_s_dlazdicema.Dlazdice.Ikona',
        ]);

        return new ObecData(
            BannerSTextemData::createFromStrapiResponse($strapiResponse['data']['attributes']['Banner']),
            SekceSDlazdicemaData::createFromStrapiResponse($strapiResponse['data']['attributes']['Sekce_s_dlazdicema']),
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

        $strapiResponse = $this->strapiClient->getApiResource('uredni-deskas', [
            'Soubory',
            'Zodpovedna_osoba.Fotka',
        ], filters: $filters, pagination: $pagination, sort: ['Datum_zverejneni:desc', 'Nadpis']);

        return UredniDeskaData::createManyFromStrapiResponse($strapiResponse);
    }

    public function getUredniDeskaData(string $slug): UredniDeskaData
    {
        $strapiResponse = $this->strapiClient->getApiResource('uredni-deskas', [
            'Soubory',
            'Zodpovedna_osoba.Fotka',
        ], filters: [
            'slug' => ['$eq' => $slug],
        ]);

        return UredniDeskaData::createFromStrapiResponse(
            $strapiResponse['data'][0]['attributes'] ?? throw new NotFound
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


    private function resourceNameToUredniDeskaField(string $resourceName): string
    {
        return match ($resourceName) {
            'urad-dokumenty-formulare' => 'Zobrazit_v_formulare',
            'urad-dokumenty-navody' => 'Zobrazit_v_navody',
            'urad-dokumenty-odpady' => 'Zobrazit_v_odpady',
            'urad-dokumenty-rozpocty' => 'Zobrazit_v_rozpocty',
            'urad-dokumenty-strategicke-dokumenty' => 'Zobrazit_v_strategicke_dokumenty',
            'urad-dokumenty-uzemni-plan' => 'Zobrazit_v_uzemni_plan',
            'urad-dokumenty-uzemni-studie' => 'Zobrazit_v_uzemni_studie',
            'urad-dokumenty-vyhlasky' => 'Zobrazit_v_vyhlasky',
            'urad-dokumenty-vyrocni-zprava' => 'Zobrazit_v_vyrocni_zpravy',
            'urad-dokumenty-zivotni-situace' => 'Zobrazit_v_zivotni_situace',
            'urad-povinne-zverejnovane-informace' => 'Zobrazit_v_poskytnute_informace',
            'urad-dokumenty-verejnopravni-smlouvy' => 'Zobrazit_v_verejnopravni_smlouvy',
            'urad-dokumenty-volby' => 'Zobrazit_v_volby',
            'urad-dokumenty-projekty' => 'Zobrazit_v_projekty',
            default => throw new \LogicException('Resource not matched: ' . $resourceName),
        };
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


    public function getDokumentyUraduData(): SekceSDlazdicemaData
    {
        $strapiResponse = $this->strapiClient->getApiResource('urad-dokumenty-uradu', [
            'Sekce_s_dlazdicema.Dlazdice.Ikona',
        ]);

        return SekceSDlazdicemaData::createFromStrapiResponse($strapiResponse['data']['attributes']['Sekce_s_dlazdicema']);
    }


    /**
     * @return array<string, string>
     */
    public function getTagy(): array
    {
        $strapiResponse = $this->strapiClient->getApiResource('tagies', [], []);

        $tags = [];

        foreach ($strapiResponse['data'] ?? [] as $tagData) {
            if ($tagData['attributes']['slug'] === null) {
                continue;
            }

            $tags[$tagData['attributes']['slug']] = $tagData['attributes']['Tag'];
        }

        return $tags;
    }

    /**
     * @return array<string, string>
     */
    public function getMenu(): array
    {
        $strapiResponse = $this->strapiClient->getApiResource('menus', [], [], sort: ['Poradi']);

        return MenuData::createManyFromStrapiResponse($strapiResponse);
    }
}
