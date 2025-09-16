<?php
declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiContent;

final class UredniDeskaController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content,
    ) {}

    #[Route(path: '/uredni-deska', name: 'uredni_deska')]
    public function __invoke(Request $request): Response
    {
        $rok = $request->query->get('rok');
        $year = $rok !== null ? (int) $rok : null;

        $firstYear = $this->content->getUredniDeskaFirstYear();
        $lastYear = $this->content->getUredniDeskaLastYear();

        return $this->render('uredni_deska.html.twig', [
            'uredni_desky' => $this->content->getUredniDeskyData(year: $year, shouldHideIfExpired: $year === null),
            'kategorie_uredni_desky' => $this->content->getKategorieUredniDesky(),
            'active_kategorie' => null,
            'first_year' => $firstYear,
            'last_year' => $lastYear,
        ]);
    }
}
