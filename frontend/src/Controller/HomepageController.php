<?php
declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiContent;

final class HomepageController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content
    ) {}

    #[Route(path: '/', name: 'homepage')]
    public function __invoke(): Response
    {
        return $this->render('homepage.html.twig', [
            'aktuality' => $this->content->getAktualityData(limit: 4),
            'uredni_deska' => $this->content->getUredniDeskyData(limit: 6, shouldHideIfExpired: true),
            'upcoming_events' => $this->content->getRecentKalendarAkciData(),
        ]);
    }
}
