<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiContent;

final class EventDetailController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content
    ) {}


    #[Route('/kalendar-akci/{slug}', name: 'detail_akce')]
    public function __invoke(string $slug): Response
    {
        try {
            $event = $this->content->getDetailAkceData($slug);

            if (
                ($event->Dokumenty ?? []) === []
                && ($event->Galerie ?? []) === []
                && $event->Popis === null
                && $event->FotkaDetail === null
            ) {
                return $this->redirectToRoute('kalendar_akci');
            }

            return $this->render('detail_eventu.html.twig',[
                'event' => $event,
            ]);
        } catch (ClientException) {
            throw $this->createNotFoundException();
        }
    }
}
