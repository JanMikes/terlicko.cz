<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class StrukturaUraduData
{
    public function __construct(
        public readonly BannerSTextemData $Banner,
        public readonly string $Obsah,

        /**
         * @var array<ClovekData> $Struktura_uradu
         */
        public readonly array $Struktura_uradu,

        /**
         * @var array<ClovekData> $Struktura_stavebniho_uradu
         */
        public readonly array $Struktura_stavebniho_uradu,
    ) {}


    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        return new self(
            BannerSTextemData::createFromStrapiResponse($data['Banner']),
            $data['Obsah'],
            ClovekData::createManyFromStrapiResponse($data['Struktura_uradu']),
            ClovekData::createManyFromStrapiResponse($data['Struktura_stavebniho_uradu']),
        );
    }
}
