<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiContent;
use Terlicko\Web\Value\Content\Data\KategorieUredniDesky;
use Terlicko\Web\Value\Content\Exception\InvalidKategorie;

final class UredniDeskaKategorieFilterController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content
    ) {}


    #[Route('/uredni-deska/kategorie/{kategorie}', name: 'uredni_deska_kategorie_filter')]
    public function __invoke(string $kategorie, Request $request): Response
    {
        $rok = $request->query->get('rok');
        $year = $rok !== null ? (int) $rok : null;

        try {
            $firstYear = $this->content->getUredniDeskaFirstYear(category: $kategorie);
            $lastYear = $this->content->getUredniDeskaLastYear(category: $kategorie);

            return $this->render('uredni_deska.html.twig', [
                'uredni_desky' => $this->content->getUredniDeskyData(category: $kategorie, year: $year, shouldHideIfExpired: $year === null),
                'kategorie_uredni_desky' => $this->content->getKategorieUredniDesky(),
                'active_kategorie' => $kategorie,
                'first_year' => $firstYear,
                'last_year' => $lastYear,
            ]);
        } catch (InvalidKategorie) {
            throw $this->createNotFoundException();
        }
    }
}
