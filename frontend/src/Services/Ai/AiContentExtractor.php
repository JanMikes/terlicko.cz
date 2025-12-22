<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use DateTimeImmutable;
use Terlicko\Web\Services\Strapi\StrapiContent;
use Terlicko\Web\Services\Strapi\StrapiLinkHelper;
use Terlicko\Web\Value\Ai\AiContentItem;
use Terlicko\Web\Value\Content\Data\KalendarAkciData;
use Terlicko\Web\Value\Content\Data\UredniDeskaData;

/**
 * Extracts and normalizes content from Strapi for AI RAG ingestion
 */
readonly final class AiContentExtractor
{
    private const PUBLIC_BASE_URL = 'https://terlicko.cz';

    public function __construct(
        private StrapiContent $strapiContent,
        private StrapiLinkHelper $strapiLinkHelper,
        private ContentNormalizer $contentNormalizer,
    ) {}

    /**
     * Extract all content types
     *
     * @return iterable<AiContentItem>
     */
    public function extractAll(): iterable
    {
        yield from $this->extractAktuality();
        yield from $this->extractSekce();
        yield from $this->extractUredniDeska();
        yield from $this->extractKalendarAkci();
    }

    /**
     * Extract news/aktuality content
     *
     * @return iterable<AiContentItem>
     */
    public function extractAktuality(): iterable
    {
        $aktuality = $this->strapiContent->getAktualityData();

        foreach ($aktuality as $item) {
            $url = self::PUBLIC_BASE_URL . '/aktuality/' . $item->slug;

            yield new AiContentItem(
                url: $url,
                title: $item->Nadpis,
                type: 'aktuality',
                normalizedText: $item->Popis,
                createdAt: $item->DatumZverejneni,
            );
        }
    }

    /**
     * Extract section pages content
     *
     * @return iterable<AiContentItem>
     */
    public function extractSekce(): iterable
    {
        $sectionSlugs = $this->strapiContent->getSectionSlugs();

        foreach ($sectionSlugs as $slug => $sectionBasic) {
            $section = $this->strapiContent->getSekceData($slug);
            $relativeUrl = $this->strapiLinkHelper->getLinkForSlug($slug);

            // Skip sections with invalid URLs
            if ($relativeUrl === '#') {
                continue;
            }

            $url = self::PUBLIC_BASE_URL . $relativeUrl;

            $normalizedText = $this->normalizeSekceContent($section);

            // Skip sections with no meaningful content
            if (trim($normalizedText) === '') {
                continue;
            }

            yield new AiContentItem(
                url: $url,
                title: $section->Nazev,
                type: 'sekce',
                normalizedText: $normalizedText,
                createdAt: new DateTimeImmutable(),
            );
        }
    }

    /**
     * Extract official board content
     *
     * @return iterable<AiContentItem>
     */
    public function extractUredniDeska(): iterable
    {
        $uredniDesky = $this->strapiContent->getUredniDeskyData();

        foreach ($uredniDesky as $item) {
            $url = self::PUBLIC_BASE_URL . '/uredni-deska/dokument/' . $item->slug;
            $normalizedText = $this->normalizeUredniDeskaContent($item);

            yield new AiContentItem(
                url: $url,
                title: $item->Nadpis,
                type: 'uredni_deska',
                normalizedText: $normalizedText,
                createdAt: $item->Datum_zverejneni,
            );
        }
    }

    /**
     * Extract calendar events content
     *
     * @return iterable<AiContentItem>
     */
    public function extractKalendarAkci(): iterable
    {
        $events = $this->strapiContent->getKalendarAkciData();

        foreach ($events as $item) {
            $normalizedText = $this->normalizeKalendarAkciContent($item);
            $title = $item->Nazev ?? 'Událost';

            // Skip events without meaningful content
            if (trim($normalizedText) === '') {
                continue;
            }

            yield new AiContentItem(
                url: self::PUBLIC_BASE_URL . '/kalendar-akci',
                title: $title,
                type: 'kalendar_akci',
                normalizedText: $normalizedText,
                createdAt: $item->Datum ?? new DateTimeImmutable(),
            );
        }
    }

    /**
     * Normalize section content using component normalizer
     */
    private function normalizeSekceContent(\Terlicko\Web\Value\Content\Data\SekceData $section): string
    {
        $parts = [];

        // Add meta description if available
        if ($section->Meta_description !== null && $section->Meta_description !== '') {
            $parts[] = $section->Meta_description;
        }

        // Normalize all components
        foreach ($section->Komponenty as $component) {
            $normalized = $this->contentNormalizer->normalizeComponent($component);
            if ($normalized !== '') {
                $parts[] = $normalized;
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Normalize official board item content
     */
    private function normalizeUredniDeskaContent(UredniDeskaData $item): string
    {
        $parts = [];

        // Main description
        if ($item->Popis !== null && $item->Popis !== '') {
            $parts[] = $item->Popis;
        }

        // Publication date
        $parts[] = '**Datum zveřejnění:** ' . $item->Datum_zverejneni->format('d.m.Y');

        // Expiration date if set
        if ($item->Datum_stazeni !== null) {
            $parts[] = '**Platnost do:** ' . $item->Datum_stazeni->format('d.m.Y');
        }

        // Categories
        if (count($item->Kategorie) > 0) {
            $categories = array_map(fn($k) => $k->Nazev, $item->Kategorie);
            $parts[] = '**Kategorie:** ' . implode(', ', $categories);
        }

        // Responsible person
        if ($item->Zodpovedna_osoba !== null) {
            $person = $item->Zodpovedna_osoba;
            $personInfo = $person->Jmeno;
            if ($person->Email !== null) {
                $personInfo .= ', ' . $person->Email;
            }
            if ($person->Telefon !== null) {
                $personInfo .= ', ' . $person->Telefon;
            }
            $parts[] = '**Zodpovědná osoba:** ' . $personInfo;
        }

        // Files
        if (count($item->Soubory) > 0) {
            $files = array_map(fn($f) => $f->name, $item->Soubory);
            $parts[] = '**Přílohy:** ' . implode(', ', $files);
        }

        return implode("\n\n", $parts);
    }

    /**
     * Normalize calendar event content
     */
    private function normalizeKalendarAkciContent(KalendarAkciData $item): string
    {
        $parts = [];

        // Event description
        if ($item->Popis !== null && $item->Popis !== '') {
            $parts[] = $item->Popis;
        }

        // Date range
        if ($item->Datum !== null) {
            $dateStr = $item->Datum->format('d.m.Y H:i');
            if ($item->DatumDo !== null) {
                $dateStr .= ' - ' . $item->DatumDo->format('d.m.Y H:i');
            }
            $parts[] = '**Termín:** ' . $dateStr;
        }

        // Organizer
        if ($item->Poradatel !== null && $item->Poradatel !== '') {
            $parts[] = '**Pořadatel:** ' . $item->Poradatel;
        }

        // Linked news article
        if ($item->Popis !== null) {
            $parts[] = '**Související článek:** ' . $item->Popis;
        }

        return implode("\n\n", $parts);
    }
}
