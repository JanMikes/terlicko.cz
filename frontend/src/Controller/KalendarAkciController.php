<?php
declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiContent;

final class KalendarAkciController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content
    ) {}

    #[Route(path: '/kalendar-akci', name: 'kalendar_akci')]
    public function __invoke(): Response
    {
        return $this->render('kalendar_akci.html.twig', [
            'upcoming_events' => $this->content->getRecentKalendarAkciData(),
        ]);
    }
}
