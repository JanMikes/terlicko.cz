<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiApiClient;

final class StrapiClearCacheWebhookController extends AbstractController
{
    public function __construct(
        private readonly StrapiApiClient $strapiApiClient,
        #[Autowire(env: 'STRAPI_WEBHOOK_TOKEN')]
        private readonly string $webhookToken,
    ) {}

    #[Route(path: '/webhook/strapi-clear-cache', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $headerToken = $request->headers->get('X-strapi-webhook-token');

        if ($headerToken !== $this->webhookToken) {
            return $this->json(['success' => false, 'error' => 'Invalid token'], 401);
        }

        $success = $this->strapiApiClient->clearCache();

        return $this->json(['success' => $success]);
    }
}
