<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type FormularDataArray from FormularData
 */
readonly final class FormularComponentData
{
    public function __construct(
        public FormularData $formular,
    ) {}

    /**
     * @param array{formular: FormularDataArray} $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            FormularData::createFromStrapiResponse($data['formular']),
        );
    }
}
