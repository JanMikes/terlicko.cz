<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;

final class AktualitaData
{
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public readonly int|null $id,
        public readonly string $Nadpis,
        public readonly DateTimeImmutable $DatumZverejneni,
        public readonly null|string $Obrazek,
        public readonly string|null $Video_youtube,

        /**
         * @var string[] $Galerie
         */
        public readonly array $Galerie,

        public readonly ClovekData|null $Zverejnil,

        /**
         * @var array<string, string> $Tagy,
         */
        public readonly array $Tagy,

        public readonly string $Popis,

        public readonly null|string $slug,

        /**
         * @var array<FileData> $Soubory
         */
        public readonly array $Soubory,
    ) {}


    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        $tags = [];

        foreach ($data['Tagy'] ?? [] as $tagData) {
            /** @var array{slug: null|string, Tag: string} $tagData */
            if ($tagData['slug'] === null) {
                continue;
            }

            $tags[$tagData['slug']] = $tagData['Tag'];
        }

        $datumZverejneni = DateTimeImmutable::createFromFormat('Y-m-d', $data['Datum_zverejneni']);

        /** @var array<array{url: string}> $galerieData */
        $galerieData = $data['Galerie'];
        $galerie = $galerieData ? array_map(fn(array $item): string => $item['url'], $galerieData) : [];

        $zverejnil = $data['Zverejnil'] ? ClovekData::createFromStrapiResponse($data['Zverejnil']) : null;

        return new self(
            $id,
            $data['Nadpis'],
            $datumZverejneni,
            $data['Obrazek']['url'] ?? null,
            $data['Video_youtube'],
            $galerie,
            $zverejnil,
            $tags,
            $data['Popis'],
            $data['slug'],
            isset($data['Soubory']) ? FileData::createManyFromStrapiResponse($data['Soubory']) : [],
        );
    }
}
