<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Http;

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Luzrain\WorkermanBundle\Reboot\RebootStrategyInterface;
use Luzrain\WorkermanBundle\Utils;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

final class RequestHandler
{
    private PsrHttpFactory $psrHttpFactory;
    private WorkermanPsrHttpFactory $workermanPsrHttpFactory;
    private HttpFoundationFactoryInterface $httpFoundationFactory;
    private FinfoMimeTypeDetector $mimeTypedetector;

    public function __construct(
        private KernelInterface $kernel,
        private RebootStrategyInterface $rebootStrategy,
        ServerRequestFactoryInterface $serverRequestFactory,
        private StreamFactoryInterface $streamFactory,
        UploadedFileFactoryInterface $uploadedFileFactory,
        private ResponseFactoryInterface $responseFactory,
    ) {
        $this->psrHttpFactory = new PsrHttpFactory($serverRequestFactory, $this->streamFactory, $uploadedFileFactory, $this->responseFactory);
        $this->workermanPsrHttpFactory = new WorkermanPsrHttpFactory($serverRequestFactory, $this->streamFactory);
        $this->httpFoundationFactory = new HttpFoundationFactory();
        $this->mimeTypedetector = new FinfoMimeTypeDetector();
    }

    public function __invoke(TcpConnection $connection, Request $workermanRequest): void
    {
        if (PHP_VERSION_ID >= 80200) {
            \memory_reset_peak_usage();
        }

        $request = $this->workermanPsrHttpFactory->createRequest($workermanRequest);
        $shouldCloseConnection = $this->shouldCloseConnection($request);

        if (\is_file($file = $this->getPublicPathFile($request))) {
            $response = $this->responseFactory->createResponse();
            $response = $response->withHeader('Content-Type', $this->mimeTypedetector->detectMimeTypeFromPath($file));
            $response = $response->withBody($this->streamFactory->createStreamFromFile($file));

            foreach ($this->generateResponse($response) as $chunk) {
                $connection->send($chunk, true);
            }

            if ($shouldCloseConnection) {
                $connection->close();
            }

            return;
        }

        $this->kernel->boot();
        $symfonyRequest = $this->httpFoundationFactory->createRequest($request);
        $symfonyResponse = $this->kernel->handle($symfonyRequest);
        $response = $this->psrHttpFactory->createResponse($symfonyResponse);

        if ($shouldCloseConnection) {
            $response = $response->withAddedHeader('Connection', 'close');
        }

        foreach ($this->generateResponse($response) as $chunk) {
            $connection->send($chunk, true);
        }

        if ($shouldCloseConnection) {
            $connection->close();
        }

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($symfonyRequest, $symfonyResponse);
        }

        if ($this->rebootStrategy->shouldReboot()) {
            Utils::reboot();
        }
    }

    private function shouldCloseConnection(ServerRequestInterface $request): bool
    {
        return $request->getProtocolVersion() === '1.0' || $request->getHeaderLine('Connection') === 'close';
    }

    private function getPublicPathFile(ServerRequestInterface $request): string
    {
        $checkFile = "{$this->kernel->getProjectDir()}/public{$request->getUri()->getPath()}";
        $checkFile = str_replace('..', '/', $checkFile);

        return $checkFile;
    }

    private function generateResponse(ResponseInterface $response): \Generator
    {
        yield 'HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase();

        if ($response->getHeaderLine('Transfer-Encoding') === '' && $response->getHeaderLine('Content-Length') === '') {
            yield "\r\nContent-Length: " . $response->getBody()->getSize();
        }
        if ($response->getHeaderLine('Content-Type') === '') {
            yield "\r\nContent-Type: text/html";
        }
        if ($response->getHeaderLine('Connection') === '') {
            yield "\r\nConnection: keep-alive";
        }
        if ($response->getHeaderLine('Server') === '') {
            yield "\r\nServer: workerman";
        }
        foreach ($response->getHeaders() as $name => $values) {
            yield "\r\n$name: " . implode(', ', $values);
        }

        yield "\r\n\r\n";

        $response->getBody()->seek(0);
        while (!$response->getBody()->eof()) {
            yield $response->getBody()->read(2048);
        }
        $response->getBody()->close();
    }
}
