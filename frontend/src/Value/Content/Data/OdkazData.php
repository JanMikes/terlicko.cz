<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type FileDataArray from FileData
 * @phpstan-type OdkazDataArray array{
 *     sekce: null|array{slug: string},
 *     URL: null|string,
 *     Kotva: null|string,
 *     Soubor: null|FileDataArray
 * }
 */
readonly final class OdkazData
{
    public function __construct(
        public null|string $sekceSlug,
        public null|string $url,
        public null|string $Kotva,
        public null|FileData $Soubor,
    ) {
    }

    /**
     * @param OdkazDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        $url = null;
        $slug = null;

        if ($data['URL'] !== null) {
            $url = ltrim($data['URL'], '/');

            if (str_starts_with($url, 'http') === false) {
                $url = '/' . $url;
            }
        }

        if ($data['sekce'] !== null) {
            $slug = $data['sekce']['slug'];
        }

        return new self(
            sekceSlug: $slug,
            url: $url,
            Kotva: $data['Kotva'],
            Soubor: $data['Soubor'] !== null ? FileData::createFromStrapiResponse($data['Soubor']) : null,
        );
    }
}
