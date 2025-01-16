<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiContent;

final class DetailUredniDeskyController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content
    ) {}


    #[Route('/uredni-deska/dokument/{slug}', name: 'detail_uredni_desky')]
    public function __invoke(string $slug, Request $request): Response
    {
        try {
            return $this->render('detail_uredni_desky.html.twig', [
                'uredni_deska' => $this->content->getUredniDeskaData($slug),
            ]);
        } catch (ClientException) {
            throw $this->createNotFoundException();
        }
    }
}
