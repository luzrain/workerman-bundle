<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Test\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class Controller extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function __invoke(): Response
    {
        return new Response(
            content: 'hello from test controller',
            headers: ['Content-Type' => 'text/plain'],
        );
    }
}
