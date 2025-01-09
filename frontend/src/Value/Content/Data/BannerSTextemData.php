<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class BannerSTextemData
{
    public function __construct(
        public readonly string $Nadpis,
        public readonly string $Obrazek,
        public readonly string $Text_pod_nadpisem,
    ) {}


    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        return new self(
            $data['Nadpis'],
            $data['Obrazek']['data']['attributes']['url'],
            $data['Text_pod_nadpisem'],
        );
    }
}
