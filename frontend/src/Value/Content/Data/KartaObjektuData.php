<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class KartaObjektuData
{
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public readonly string $Obrazek,
        public readonly string $Nazev,
        public readonly null|string $Telefon,
        public readonly null|string $Email,
        public readonly null|string $Odkaz_web,
        public readonly null|string $Odkaz_mapa,
        public readonly null|string $Adresa,
    ) {}


    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        return new self(
            $data['Obrazek']['data']['attributes']['url'],
            $data['Nazev'],
            $data['Telefon'],
            $data['Email'],
            $data['Odkaz_web'],
            $data['Odkaz_mapa'],
            $data['Adresa'],
        );
    }
}
