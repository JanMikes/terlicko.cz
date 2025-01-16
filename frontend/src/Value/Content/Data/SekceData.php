<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type SekceDataArray array{
 *      id: int,
 *      Nazev: string,
 *      slug: string,
 *      Meta_description: string,
 *      Komponenty: array<array{__component: string}>,
 *  }
 */
readonly final class SekceData
{
    /**
     * @param array<Component> $Komponenty
     */
    public function __construct(
        public string $Nazev,
        public string $slug,
        public string|null $Meta_description,
        public array $Komponenty,
    ) {
    }

    /**
     * @param SekceDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        $components = [];

        foreach ($data['Komponenty'] as $component) {
            $components[] = match($component['__component']) {
                'komponenty.nadpis' => new Component('Nadpis', NadpisComponentData::createFromStrapiResponse($component)),
                'komponenty.textove-pole' => new Component('TextovePole', TextovePoleComponentData::createFromStrapiResponse($component)),
                'komponenty.aktuality' => new Component('Aktuality', AktualityComponentData::createFromStrapiResponse($component)),
                'komponenty.formular' => new Component('Formular', FormularComponentData::createFromStrapiResponse($component)),
                'komponenty.galerie' => new Component('Galerie', GalerieComponentData::createFromStrapiResponse($component)),
                'komponenty.obrazek' => new Component('Obrazek', ObrazekComponentData::createFromStrapiResponse($component)),
                'komponenty.rozdelovnik' => new Component('Rozdelovnik', RozdelovnikComponentData::createFromStrapiResponse($component)),
                'komponenty.samosprava' => new Component('Samosprava', SamospravaComponentData::createFromStrapiResponse($component)),
                'komponenty.sekce-s-dlazdicema' => new Component('SekceSDlazdicema', SekceSDlazdicemaComponentData::createFromStrapiResponse($component)),
                'komponenty.soubory-ke-stazeni' => new Component('SouboryKeStazeni', SouboryKeStazeniComponentData::createFromStrapiResponse($component)),
                'komponenty.tlacitka' => new Component('Tlacitka', TlacitkaComponentData::createFromStrapiResponse($component)),
                'komponenty.uredni-deska' => new Component('UredniDeska', new \stdClass()),
                default => throw new \Exception('Unknown component ' . $component['__component']),
            };
        }

        return new self(
            $data['Nazev'],
            $data['slug'],
            $data['Meta_description'],
            $components,
        );
    }
}
