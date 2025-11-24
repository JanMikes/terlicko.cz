<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller\Ai;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Ai\FileExtractor;

#[Route('/ai/files.json', name: 'ai_files_json')]
final class AiFilesJsonController extends AbstractController
{
    public function __construct(
        private readonly FileExtractor $fileExtractor,
    ) {
    }

    public function __invoke(): Response
    {
        $files = $this->fileExtractor->extractPdfFiles();

        return new JsonResponse([
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'count' => count($files),
            'files' => $files,
        ]);
    }
}
