<?php
declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PristupnostController extends AbstractController
{
    #[Route(path: '/prohlaseni-o-pristupnosti', name: 'pristupnost')]
    public function __invoke(): Response
    {
        return $this->render('pristupnost.html.twig');
    }
}
