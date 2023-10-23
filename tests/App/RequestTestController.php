<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Test\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
            'files' => $this->normalizeFiles($request->files->all()),
            'cookies' => $request->cookies->all(),
            'raw_request' => $request->getContent(),
        ]);
    }

    private function normalizeFiles(array $files): array
    {
        $array = [];
        foreach ($files as $name => $file) {
            if ($file instanceof UploadedFile) {
                $array[] = [
                    'name' => $name,
                    'filename' => $file->getClientOriginalName(),
                    'extension' => $file->getClientOriginalExtension(),
                    'content' => $file->getContent(),
                    'size' => $file->getSize(),
                ];
            }
        }

        return $array;
    }
}
