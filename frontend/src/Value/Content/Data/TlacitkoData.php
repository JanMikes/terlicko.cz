<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class TlacitkoData
{
    use CanCreateManyFromStrapiResponse;


    public function __construct(
        public string $Text,
        public string $Odkaz,
    ) {}

    /**
     * @param array{Text: string, Odkaz: string} $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self($data['Text'], $data['Odkaz']);
    }
}
