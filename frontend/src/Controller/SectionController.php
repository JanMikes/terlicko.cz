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

    #[Route(path: '/sekce/{slug}', name: 'section')]
    public function __invoke(string $slug): Response
    {
        try {
            return $this->render('section.html.twig',[
                'sekce' => $this->content->getSekceData($slug),
            ]);
        } catch (ClientException) {
            throw $this->createNotFoundException();
        }
    }
}
