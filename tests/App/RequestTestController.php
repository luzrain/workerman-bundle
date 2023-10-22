<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Test\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class RequestTestController extends AbstractController
{
    #[Route('/request_test', name: 'app_request_test')]
    public function __invoke(Request $request): Response
    {
        return $this->json([
            'headers' => $request->headers->all(),
            'get' => $request->query->all(),
            'post' => $request->request->all(),
            'files' => $request->files->all(),
            'cookies' => $request->cookies->all(),
            'raw_request' => $request->getContent(),
        ]);
    }
}
