<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type FormularDataArray array{
 *     Zobrazovat_nazev: null|bool,
 *     Email_prijemce: string,
 *     Email_predmet: string,
 *     Nazev_formulare: string,
 *     Pole: array<array{__component: string}>,
 *  }
 */
readonly final class FormularData
{
    /**
     * @param array<Component> $Pole
     */
    public function __construct(
        public bool $Zobrazovat_nazev,
        public string $Email_prijemce,
        public string $Email_predmet,
        public string $Nazev_formulare,
        public array $Pole,
    ) {}

    /**
     * @param FormularDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        $components = [];

        foreach ($data['Pole'] as $component) {
            $components[] = match($component['__component']) {
                'komponenty.nadpis' => new Component('Nadpis', NadpisComponentData::createFromStrapiResponse($component)),
                'komponenty.textove-pole' => new Component('TextovePole', TextovePoleComponentData::createFromStrapiResponse($component)),
                'elementy.pole-formulare' => new Component('PoleFormulare', PoleFormulareData::createFromStrapiResponse($component)),
                'elementy.pole-formulare-s-moznostmi' => new Component('PoleFormulareSMoznostmi', PoleFormulareSMoznostmiData::createFromStrapiResponse($component)),
                default => throw new \Exception('Unknown component ' . $component['__component']),
            };
        }

        return new self(
            $data['Zobrazovat_nazev'] ?? true,
            $data['Email_prijemce'],
            $data['Email_predmet'],
            $data['Nazev_formulare'],
            $components,
        );
    }
}
