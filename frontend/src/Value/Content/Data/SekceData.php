<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use Terlicko\Web\Value\Content\Data\Component\NadpisComponentData;

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
            if ($component['__component'] === 'komponenty.nadpis') {
                $components[] = new Component('nadpis', NadpisComponentData::createFromStrapiResponse($component));
            }

            /*
            $components[] = match($component['__component']) {
                'komponenty.aktuality' => '',
                'komponenty.formular' => '',
                'komponenty.galerie' => '',
                'komponenty.obrazek' => '',
                'komponenty.rozdelovnik' => '',
                'komponenty.samosprava' => '',
                'komponenty.sekce-s-dlazdicema' => '',
                'komponenty.soubory-ke-stazeni' => '',
                'komponenty.textove-pole' => '',
                'komponenty.tlacitka' => '',
                'komponenty.uredni-deska' => '',
                default => throw new \Exception('Unknown component'),
            };
            */
        }

        return new self(
            $data['Nazev'],
            $data['Meta_description'],
            $components,
        );
    }
}
