<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Strapi;

final class StrapiLinkHelper
{
    /** @var null| array<int, string> */
    private null|array $x = null;

    public function __construct(
        readonly private StrapiContent $strapiContent
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function get(): array
    {
        if ($this->x === null) {
            $this->x = $this->strapiContent->get();
        }

        return $this->x;
    }
}
