<?php
declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UredniDeskaController extends AbstractController
{
    #[Route(path: '/uredni-deska', name: 'uredni_deska')]
    public function __invoke(): Response
    {
        return $this->render('uredni_deska.html.twig');
    }
}
