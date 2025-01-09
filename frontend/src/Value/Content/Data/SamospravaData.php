<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

use Nette\Utils\Strings;

final class SamospravaData
{
    use HasUredniDeskaYears;

    public function __construct(
        public readonly string $Nadpis,
        public readonly string|null $Obsah,
        public readonly string|null $Nadpis_uredni_desky,
        public readonly string $Kategorie_uredni_desky,

        /**
         * @var array<ClovekData> $Lide
         */
        public readonly array $Lide,

        /**
         * @var array<UredniDeskaData> $Uredni_deska
         */
        public readonly array $Uredni_deska,

        /**
         * @var array<int>
         */
        public readonly array $Uredni_deska_roky,
    ) {
    }


    /**
     * @return array<self>
     */
    public static function createManyFromStrapiResponse(array $data): array
    {
        $objects = [];
        $data = $data['data'] ?? $data;

        foreach ($data as $singleObjectData) {
            $objects[] = self::createFromStrapiResponse($singleObjectData['attributes'] ?? $singleObjectData, $singleObjectData['id'] ?? null);
        }

        return $objects;
    }


    public static function createFromStrapiResponse(array $data, int|null $id = null): self
    {
        return new self(
            $data['Nadpis'],
            $data['Obsah'],
            $data['Nadpis_uredni_desky'],
            $data['Kategorie_uredni_desky'],
            ClovekData::createManyFromStrapiResponse($data['Lide']),
            $data['Uredni_deska'],
            self::getUredniDeskaYears($data['Uredni_deska']),
        );
    }
}
