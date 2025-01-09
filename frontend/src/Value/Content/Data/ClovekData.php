<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class ClovekData
{
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $Jmeno,
        public string $Funkce,
        public string|null $Email,
        public string|null $Telefon,
        public string $Pohlavi,
        public string|null $Fotka,
    )
    {
    }


    public function isMuz(): bool
    {
        return $this->Pohlavi === 'muz';
    }


    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        // Special type, data is wrapped and 'Funkce' will overwrite
        if (isset($data['Clovek'], $data['Funkce'])) {
            $funkce = $data['Funkce'];
            $data = $data['Clovek'];
            $data['Funkce'] = $funkce;
        }

        return new self(
            $data['Jmeno'],
            $data['Funkce'],
            $data['Email'],
            $data['Telefon'],
            $data['Pohlavi'],
            $data['Fotka'] ? $data['Fotka']['url'] : null
        );
    }
}
