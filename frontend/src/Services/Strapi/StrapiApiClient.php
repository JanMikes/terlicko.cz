<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Strapi;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly final class StrapiApiClient
{
    public function __construct(
        private HttpClientInterface $strapiClient,
    ) {}


    /**
     * @param null|array<int|string, mixed> $populate
     * @param null|array<string> $fields
     * @param null|array<string, mixed> $filters
     * @param null|array{limit: int, start: int} $pagination
     * @param null|array<string> $sort
     *
     * @return array<mixed>
     */
    public function getApiResource(
        string $resourceName,
        array|null $populate = null,
        array|null $fields = null,
        array|null $filters = null,
        array|null $pagination = null,
        array|null $sort = null,
    ): array
    {
        $query = [
            'populate' => $populate === null ? '*' : $populate,
            'fields' => $fields === null ? '*' : $fields,
        ];

        if ($pagination !== null) {
            $query['pagination'] = $pagination;
        }

        if ($sort !== null) {
            $query['sort'] = $sort;
        }

        if ($filters !== null) {
            $query['filters'] = $filters;
        }

        $response = $this->strapiClient->request('GET', '/api/' . $resourceName, [
            'query' => $query
        ]);

        return $response->toArray();
    }
}
