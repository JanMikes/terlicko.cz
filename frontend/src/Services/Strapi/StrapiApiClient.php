<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Strapi;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly final class StrapiApiClient
{
    public function __construct(
        private HttpClientInterface $strapiClient,
        private TagAwareCacheInterface $cache,
    ) {}

    public function clearCache(): bool
    {
        return $this->cache->invalidateTags(['strapi']);
    }


    /**
     * @param null|array<string> $fields
     * @param null|array<string, mixed> $filters
     * @param null|array{limit: int, start: int} $pagination
     * @param null|array<string> $sort
     *
     * @return array<mixed>
     */
    public function getApiResource(
        string $resourceName,
        int $populateLevel = 3,
        array|null $fields = null,
        array|null $filters = null,
        array|null $pagination = null,
        array|null $sort = null,
    ): array
    {
        $query = [
            'pLevel' => $populateLevel,
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

        $key = $resourceName . '?' . http_build_query($query);

        return $this->cache->get($key, function(ItemInterface $item) use ($resourceName, $query): array {
            $item->tag('strapi');
            $item->expiresAfter(3600 * 24 * 7); // 7 days

            $response = $this->strapiClient->request('GET', '/api/' . $resourceName, [
                'query' => $query
            ]);

            return $response->toArray();
        });
    }
}
