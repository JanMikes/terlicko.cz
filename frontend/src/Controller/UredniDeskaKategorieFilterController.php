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
        try {
            $kategorieUredniDesky = KategorieUredniDesky::fromSlug($kategorie);

            return $this->render('uredni_deska.html.twig', [
                'uredni_desky' => $this->content->getUredniDeskyDataFilteredByKategorie($kategorie),
                'kategorie_uredni_desky' => $kategorieUredniDesky,
            ]);
        } catch (InvalidKategorie) {
            throw $this->createNotFoundException();
        }
    }
}
