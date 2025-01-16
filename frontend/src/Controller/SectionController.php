<?php
declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiContent;

final class SectionController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content,
    ) {
    }

    #[Route(path: '/{path}', name: 'section', requirements: ['path' => '.*'], priority: -10)]
    public function __invoke(string $path): Response
    {
        $breadcrumb = explode('/', $path);
        $currentSectionSlug = array_pop($breadcrumb);

        try {
            return $this->render('section.html.twig',[
                'sekce' => $this->content->getSekceData($currentSectionSlug),
                'breadcrumb' => $breadcrumb,
            ]);
        } catch (ClientException) {
            throw $this->createNotFoundException();
        }
    }
}
