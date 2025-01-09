<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiContent;

final class DetailAktualityController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content
    ) {}


    #[Route('/aktualita/{slug}', name: 'detail_aktuality')]
    public function __invoke(string $slug): Response
    {
        try {
            return $this->render('detail_aktuality.html.twig',[
                'aktualita' => $this->content->getAktualitaData($slug),
            ]);
        } catch (ClientException) {
            throw $this->createNotFoundException();
        }
    }
}
