<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type SekceDataArray array{
 *      Nazev: string,
 *      Meta_description: string,
 *      Komponenty: array<mixed>,
 *  }
 */
readonly final class SekceData
{
    /**
     * @param array<Component> $Komponenty
     */
    public function __construct(
        public string $Nazev,
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
                'komponenty.aktuality' => new Component('Aktuality', new \stdClass()),
                'komponenty.formular' => new Component('Formular', FormularComponentData::createFromStrapiResponse($component)),
                'komponenty.galerie' => new Component('Galerie', new \stdClass()),
                'komponenty.obrazek' => new Component('Obrazek', new \stdClass()),
                'komponenty.rozdelovnik' => new Component('Rozdelovnik', RozdelovnikComponentData::createFromStrapiResponse($component)),
                'komponenty.samosprava' => new Component('Samosprava', new \stdClass()),
                'komponenty.sekce-s-dlazdicema' => new Component('SekceSDlazdicema', new \stdClass()),
                'komponenty.soubory-ke-stazeni' => new Component('SouboryKeStazeni', new \stdClass()),
                'komponenty.tlacitka' => new Component('Tlacitka', TlacitkaComponentData::createFromStrapiResponse($component)),
                'komponenty.uredni-deska' => new Component('UredniDeska', new \stdClass()),
                default => throw new \Exception('Unknown component ' . $component['__component']),
            };
        }

        return new self(
            $data['Nazev'],
            $data['Meta_description'],
            $components,
        );
    }
}
