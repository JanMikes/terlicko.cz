<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Terlicko\Web\Value\Content\Data\AktualityComponentData;
use Terlicko\Web\Value\Content\Data\Component;
use Terlicko\Web\Value\Content\Data\FormularComponentData;
use Terlicko\Web\Value\Content\Data\GalerieComponentData;
use Terlicko\Web\Value\Content\Data\KartyComponentData;
use Terlicko\Web\Value\Content\Data\NadpisComponentData;
use Terlicko\Web\Value\Content\Data\ObrazekComponentData;
use Terlicko\Web\Value\Content\Data\PasKaretSArgumentyComponentData;
use Terlicko\Web\Value\Content\Data\PasSObrazkemComponentData;
use Terlicko\Web\Value\Content\Data\ProgramKinaComponentData;
use Terlicko\Web\Value\Content\Data\RozdelovnikComponentData;
use Terlicko\Web\Value\Content\Data\SamospravaComponentData;
use Terlicko\Web\Value\Content\Data\SekceSDlazdicemaComponentData;
use Terlicko\Web\Value\Content\Data\SouboryKeStazeniComponentData;
use Terlicko\Web\Value\Content\Data\TerminyAkciComponentData;
use Terlicko\Web\Value\Content\Data\TextovePoleComponentData;
use Terlicko\Web\Value\Content\Data\TimelineComponentData;
use Terlicko\Web\Value\Content\Data\TipyNaVyletComponentData;
use Terlicko\Web\Value\Content\Data\TlacitkaComponentData;
use Terlicko\Web\Value\Content\Data\UredniDeskaComponentData;
use Terlicko\Web\Value\Content\Data\VizitkyComponentData;

/**
 * Normalizes Strapi components to markdown text for RAG indexing
 */
readonly final class ContentNormalizer
{
    /**
     * Normalize a component to markdown text
     *
     * @param Component $component
     * @return string Markdown formatted text
     */
    public function normalizeComponent(Component $component): string
    {
        return match ($component->type) {
            'Nadpis' => $component->data instanceof NadpisComponentData ? $this->normalizeNadpis($component->data) : '',
            'TextovePole' => $component->data instanceof TextovePoleComponentData ? $this->normalizeTextovePole($component->data) : '',
            'PasSObrazkem' => $component->data instanceof PasSObrazkemComponentData ? $this->normalizePasSObrazkem($component->data) : '',
            'Karty' => $component->data instanceof KartyComponentData ? $this->normalizeKarty($component->data) : '',
            'Vizitky' => $component->data instanceof VizitkyComponentData ? $this->normalizeVizitky($component->data) : '',
            'Samosprava' => $component->data instanceof SamospravaComponentData ? $this->normalizeSamosprava($component->data) : '',
            'SekceSDlazdicema' => $component->data instanceof SekceSDlazdicemaComponentData ? $this->normalizeSekceSDlazdicema($component->data) : '',
            'Tlacitka' => $component->data instanceof TlacitkaComponentData ? $this->normalizeTlacitka($component->data) : '',
            'SouboryKeStazeni' => $component->data instanceof SouboryKeStazeniComponentData ? $this->normalizeSouboryKeStazeni($component->data) : '',
            'PasKaretSArgumenty' => $component->data instanceof PasKaretSArgumentyComponentData ? $this->normalizePasKaretSArgumenty($component->data) : '',
            'TipyNaVylet' => $component->data instanceof TipyNaVyletComponentData ? $this->normalizeTipyNaVylet($component->data) : '',
            'Timeline' => $component->data instanceof TimelineComponentData ? $this->normalizeTimeline($component->data) : '',
            'ProgramKina' => $component->data instanceof ProgramKinaComponentData ? $this->normalizeProgramKina($component->data) : '',
            'TerminyAkci' => $component->data instanceof TerminyAkciComponentData ? $this->normalizeTerminyAkci($component->data) : '',
            'Formular' => $component->data instanceof FormularComponentData ? $this->normalizeFormular($component->data) : '',

            // Dynamic components (display metadata only)
            'Aktuality' => $component->data instanceof AktualityComponentData ? $this->normalizeAktuality($component->data) : '',
            'UredniDeska' => $component->data instanceof UredniDeskaComponentData ? $this->normalizeUredniDeska($component->data) : '',

            // Visual-only components (skip)
            'Rozdelovnik' => '',
            'Galerie' => '',
            'Obrazek' => '',

            default => '',
        };
    }

    private function normalizeNadpis(NadpisComponentData $data): string
    {
        $level = match ($data->Typ) {
            'h1' => '#',
            'h2' => '##',
            'h3' => '###',
            'h4' => '####',
            default => '##',
        };

        return $level . ' ' . $data->Nadpis . "\n\n";
    }

    private function normalizeTextovePole(TextovePoleComponentData $data): string
    {
        return $data->Text . "\n\n";
    }

    private function normalizePasSObrazkem(PasSObrazkemComponentData $data): string
    {
        $text = '';

        if ($data->Nadpis !== null) {
            $text .= "## " . $data->Nadpis . "\n\n";
        }

        if ($data->Text !== null) {
            $text .= $data->Text . "\n\n";
        }

        // Include button labels
        foreach ($data->Tlacitka as $tlacitko) {
            $text .= "- " . $tlacitko->Text . "\n";
        }

        if (count($data->Tlacitka) > 0) {
            $text .= "\n";
        }

        return $text;
    }

    private function normalizeKarty(KartyComponentData $data): string
    {
        $text = '';

        foreach ($data->Karty as $karta) {
            $text .= "### " . $karta->Nazev . "\n\n";

            if ($karta->Adresa !== null) {
                $text .= "**Adresa:** " . $karta->Adresa . "\n\n";
            }

            if ($karta->Telefon !== null) {
                $text .= "**Telefon:** " . $karta->Telefon . "\n\n";
            }

            if ($karta->Email !== null) {
                $text .= "**Email:** " . $karta->Email . "\n\n";
            }
        }

        return $text;
    }

    private function normalizeVizitky(VizitkyComponentData $data): string
    {
        $text = '';

        foreach ($data->Vizitky as $vizitka) {
            if ($vizitka->Adresa !== null) {
                $text .= "**Adresa:** " . $vizitka->Adresa . "\n\n";
            }

            if ($vizitka->OteviraciDoba !== null) {
                $text .= "**Otevírací doba:** " . $vizitka->OteviraciDoba . "\n\n";
            }

            foreach ($vizitka->Lekari as $lekar) {
                $text .= "- " . $lekar->Jmeno . "\n";
            }

            foreach ($vizitka->Telefony as $telefon) {
                $label = $telefon->NazevTelefonu ?? 'Telefon';
                $text .= "**" . $label . ":** " . $telefon->Telefon . "\n\n";
            }

            $text .= "\n";
        }

        return $text;
    }

    private function normalizeSamosprava(SamospravaComponentData $data): string
    {
        $text = '';

        foreach ($data->lide as $clovekSamospravy) {
            if ($clovekSamospravy->clovek === null) {
                continue;
            }

            $clovek = $clovekSamospravy->clovek;
            $text .= "### " . $clovek->Jmeno . "\n\n";

            if ($clovekSamospravy->Funkce !== null) {
                $text .= "**Funkce:** " . $clovekSamospravy->Funkce . "\n\n";
            }

            if ($clovek->Email !== null) {
                $text .= "**Email:** " . $clovek->Email . "\n\n";
            }

            if ($clovek->Telefon !== null) {
                $text .= "**Telefon:** " . $clovek->Telefon . "\n\n";
            }
        }

        return $text;
    }

    private function normalizeSekceSDlazdicema(SekceSDlazdicemaComponentData $data): string
    {
        $text = '';

        foreach ($data->Dlazdice as $dlazdice) {
            $text .= "- " . $dlazdice->Nadpis_dlazdice;

            // Include link target for additional context
            if ($dlazdice->Odkaz->sekceSlug !== null) {
                $text .= " (sekce: " . $dlazdice->Odkaz->sekceSlug . ")";
            }

            $text .= "\n";
        }

        if (count($data->Dlazdice) > 0) {
            $text .= "\n";
        }

        return $text;
    }

    private function normalizeTlacitka(TlacitkaComponentData $data): string
    {
        $text = '';

        foreach ($data->Tlacitka as $tlacitko) {
            $text .= "- " . $tlacitko->Text . "\n";
        }

        if (count($data->Tlacitka) > 0) {
            $text .= "\n";
        }

        return $text;
    }

    private function normalizeSouboryKeStazeni(SouboryKeStazeniComponentData $data): string
    {
        $text = "**Soubory ke stažení:**\n\n";

        foreach ($data->Soubor as $soubor) {
            $text .= "- " . $soubor->Nadpis . "\n";
        }

        $text .= "\n";

        return $text;
    }

    private function normalizePasKaretSArgumenty(PasKaretSArgumentyComponentData $data): string
    {
        $text = '';

        foreach ($data->Karty as $karta) {
            $text .= "### " . $karta->Nadpis . "\n\n";

            if ($karta->Text !== null) {
                $text .= $karta->Text . "\n\n";
            }
        }

        return $text;
    }

    private function normalizeTipyNaVylet(TipyNaVyletComponentData $data): string
    {
        $text = '';

        foreach ($data->Karty as $karta) {
            if ($karta->Nadpis !== null) {
                $text .= "### " . $karta->Nadpis . "\n\n";
            }

            if ($karta->Text !== null) {
                $text .= $karta->Text . "\n\n";
            }
        }

        return $text;
    }

    private function normalizeTimeline(TimelineComponentData $data): string
    {
        $text = '';

        foreach ($data->Polozky as $polozka) {
            if ($polozka->Nadpis !== null) {
                $text .= "### " . $polozka->Nadpis . "\n\n";
            }

            if ($polozka->Text !== null) {
                $text .= $polozka->Text . "\n\n";
            }
        }

        return $text;
    }

    private function normalizeProgramKina(ProgramKinaComponentData $data): string
    {
        $text = "**Program kina:**\n\n";

        foreach ($data->Filmy as $film) {
            $text .= "### " . $film->Film . "\n\n";

            if ($film->Popis !== null) {
                $text .= $film->Popis . "\n\n";
            }

            $text .= "**Vstupné:** " . $film->Vstupne . "\n\n";

            foreach ($film->Datumy as $datum) {
                $text .= "- " . $datum->Datum->format('d.m.Y H:i') . "\n";
            }

            $text .= "\n";
        }

        return $text;
    }

    private function normalizeTerminyAkci(TerminyAkciComponentData $data): string
    {
        $text = "**Termíny akcí:**\n\n";

        foreach ($data->Terminy as $termin) {
            if ($termin->Nazev !== null) {
                $text .= "### " . $termin->Nazev . "\n\n";
            }

            if ($termin->Termin !== null) {
                $text .= "**Termín:** " . $termin->Termin->format('d.m.Y H:i') . "\n\n";
            }
        }

        return $text;
    }

    private function normalizeFormular(FormularComponentData $data): string
    {
        return "**Formulář:** " . $data->formular->Nazev_formulare . "\n\n";
    }

    private function normalizeAktuality(AktualityComponentData $data): string
    {
        $text = "**Sekce aktualit**";

        $text .= " (zobrazeno " . $data->Pocet . " položek)";

        if (count($data->kategorie) > 0) {
            $kategorie = array_map(fn($tag) => $tag->Tag, $data->kategorie);
            $text .= " - kategorie: " . implode(', ', $kategorie);
        }

        return $text . "\n\n";
    }

    private function normalizeUredniDeska(UredniDeskaComponentData $data): string
    {
        $text = "**Úředni deska**";

        $text .= " (zobrazeno " . $data->Pocet . " položek)";

        if (count($data->Kategorie) > 0) {
            $kategorie = array_map(fn($kat) => $kat->Nazev, $data->Kategorie);
            $text .= " - kategorie: " . implode(', ', $kategorie);
        }

        return $text . "\n\n";
    }
}
