<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http;
use Workerman\Psr7\ServerRequest;
use function Workerman\Psr7\response_to_string;

final class RequestHandler
{
    private PsrHttpFactory $psrHttpFactory;
    private HttpFoundationFactoryInterface $httpFoundationFactory;
    private Psr17Factory $psr17Factory;
    private FinfoMimeTypeDetector $mimeTypedetector;

    public function __construct(private KernelInterface $kernel)
    {
        $this->psr17Factory = new Psr17Factory();
        $this->httpFoundationFactory = new HttpFoundationFactory();
        $this->psrHttpFactory = new PsrHttpFactory($this->psr17Factory, $this->psr17Factory, $this->psr17Factory, $this->psr17Factory);

        $this->mimeTypedetector = new FinfoMimeTypeDetector();

        Http::requestClass(ServerRequest::class);
    }

    public function onMessage(TcpConnection $connection, ServerRequest $psrRequest): void
    {
        $checkFile = "{$this->kernel->getProjectDir()}/public{$psrRequest->getUri()->getPath()}";
        $checkFile = str_replace('..', '/', $checkFile);

        if (is_file($checkFile)) {
            $code = file_get_contents($checkFile);
            $psrResponse = new Response(200, [
                'Content-Type' => $this->mimeTypedetector->detectMimeType($checkFile, $code),
                'Last-Modified' => gmdate('D, d M Y H:i:s', filemtime($checkFile)) . ' GMT',
            ], $code);
            $connection->send(response_to_string($psrResponse), true);

            return;
        }

        $this->kernel->boot();

        $symfonyRequest = $this->httpFoundationFactory->createRequest($psrRequest);
        $symfonyResponse = $this->kernel->handle($symfonyRequest);
        $psrResponse = $this->psrHttpFactory->createResponse($symfonyResponse);
        $connection->send(response_to_string($psrResponse), true);

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($symfonyRequest, $symfonyResponse);
        }
    }
}
