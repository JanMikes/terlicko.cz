<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type PoleFormulareDataArray from PoleFormulareData
 * @phpstan-import-type PoleFormulareSMoznostmiDataArray from PoleFormulareSMoznostmiData
 * @phpstan-type FormularDataArray array{
 *      Email_prijemce: string,
 *      Email_predmet: string,
 *      Nazev_formulare: string,
 *      Pole: array<PoleFormulareDataArray|PoleFormulareSMoznostmiDataArray>,
 *  }
 */
readonly final class FormularData
{
    /**
     * @param array<PoleFormulareData|PoleFormulareSMoznostmiData> $Pole
     */
    public function __construct(
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
        $inputs = [];

        foreach ($data['Pole'] as $inputData) {
            if ($inputData['__component'] === 'elementy.pole-formulare-s-moznostmi') {
                /** @var PoleFormulareSMoznostmiDataArray $inputData */
                $inputs[] = PoleFormulareSMoznostmiData::createFromStrapiResponse($inputData);
            } else {
                /** @var PoleFormulareDataArray $inputData */
                $inputs[] = PoleFormulareData::createFromStrapiResponse($inputData);
            }
        }

        return new self(
            $data['Email_prijemce'],
            $data['Email_predmet'],
            $data['Nazev_formulare'],
            $inputs,
        );
    }
}
