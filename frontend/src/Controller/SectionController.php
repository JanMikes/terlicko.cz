<?php
declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiContent;
use Terlicko\Web\Services\Strapi\StrapiLinkHelper;

final class SectionController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content,
        readonly private StrapiLinkHelper $strapiLinkHelper,
    ) {
    }

    #[Route(path: '/{path}', name: 'section', requirements: ['path' => '.*'], priority: -10)]
    public function __invoke(string $path): Response
    {
        $breadcrumbLinks = [];
        $breadcrumbs = explode('/', $path);
        $currentSectionSlug = array_pop($breadcrumbs);
        $sections = $this->strapiLinkHelper->getSections();

        foreach ($breadcrumbs as $slug) {
            $linkforSlug = $this->strapiLinkHelper->getLinkForSlug($slug);
            $breadcrumbLinks[$linkforSlug] = $sections[$slug]->Nazev;
        }

        try {
            return $this->render('section.html.twig',[
                'sekce' => $this->content->getSekceData($currentSectionSlug),
                'breadcrumbs' => $breadcrumbLinks,
                'sections' => $this->strapiLinkHelper->getSections(),
            ]);
        } catch (ClientException) {
            throw $this->createNotFoundException();
        }
    }
}
