<?php
declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiContent;

final class UredniDeskaController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content,
    ) {}

    #[Route(path: '/uredni-deska', name: 'uredni_deska')]
    public function __invoke(): Response
    {
        return $this->render('uredni_deska.html.twig', [
            'uredni_desky' => $this->content->getUredniDeskyData(shouldHideIfExpired: true),
            'kategorie_uredni_desky' => $this->content->getKategorieUredniDesky(),
            'active_kategorie' => null,
        ]);
    }
}
