<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type OdkazDataArray from OdkazData
 * @phpstan-type TlacitkoDataArray array{
 *     Text: null|string,
 *     Odkaz: null|OdkazDataArray,
 *     Styl: null|string,
 * }
 */
readonly final class TlacitkoData
{
    private const DEFAULT_STYL = 'Styl 1';

    public function __construct(
        public string $Text,
        public OdkazData $Odkaz,
        public string $Styl,
    ) {}

    /**
     * Editors can add a button in Strapi and leave it completely blank - such button
     * has nothing to render, so it is dropped instead of blowing up the whole page.
     *
     * @param TlacitkoDataArray $data
     */
    public static function createFromStrapiResponse(array $data): null|self
    {
        $text = $data['Text'] ?? null;

        if ($text === null || trim($text) === '') {
            return null;
        }

        return new self(
            $text,
            OdkazData::createFromStrapiResponse($data['Odkaz'] ?? [
                'URL' => null,
                'Kotva' => null,
                'Soubor' => null,
            ]),
            $data['Styl'] ?? self::DEFAULT_STYL,
        );
    }

    /**
     * @param array<TlacitkoDataArray> $data
     * @return array<self>
     */
    public static function createManyFromStrapiResponse(array $data): array
    {
        $tlacitka = [];

        foreach ($data as $tlacitkoData) {
            $tlacitko = self::createFromStrapiResponse($tlacitkoData);

            if ($tlacitko !== null) {
                $tlacitka[] = $tlacitko;
            }
        }

        return $tlacitka;
    }
}
