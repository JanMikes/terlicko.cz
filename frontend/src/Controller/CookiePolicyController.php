<?php
declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CookiePolicyController extends AbstractController
{
    #[Route(path: '/zasady-cookies', name: 'cookie_policy')]
    public function __invoke(): Response
    {
        return $this->render('cookie_policy.html.twig');
    }
}
