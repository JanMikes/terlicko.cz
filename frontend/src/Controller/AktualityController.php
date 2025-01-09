<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Terlicko\Web\Services\Strapi\StrapiContent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AktualityController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content
    ) {}

    #[Route('/aktuality', name: 'aktuality')]
    public function __invoke(): Response
    {
        return $this->render('aktuality.html.twig', [
            'tagy' => $this->content->getTagy(),
            'active_tag' => null,
            'aktuality' => $this->content->getAktualityData(),
        ]);
    }
}
