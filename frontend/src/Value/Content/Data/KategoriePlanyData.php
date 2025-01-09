<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use DateTimeImmutable;

final class KategoriePlanyData
{
    use CanCreateManyFromStrapiResponse;


    public function __construct(
        public readonly null|string $Nazev,
        public readonly null|DateTimeImmutable $Datum_vyveseni,
        public readonly null|string $Obsah,

        /**
         * @var array<DokumentPlanuData> $Dokumenty
         */
        public readonly array $Dokumenty,
    ) {}


    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        return new self(
            $data['Nazev'],
            $data['Datum_vyveseni'] ? DateTimeImmutable::createFromFormat('Y-m-d', $data['Datum_vyveseni']) : null,
            $data['Obsah'],
            DokumentPlanuData::createManyFromStrapiResponse($data['Dokumenty']),
        );
    }
}
