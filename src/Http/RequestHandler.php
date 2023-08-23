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

class RequestHandler
{
    public static int $chunkSize = 2048;

    protected PsrHttpFactory $psrHttpFactory;
    protected WorkermanPsrHttpFactory $workermanPsrHttpFactory;
    protected HttpFoundationFactoryInterface $httpFoundationFactory;
    protected FinfoMimeTypeDetector $mimeTypedetector;

    public function __construct(
        private KernelInterface $kernel,
        private RebootStrategyInterface $rebootStrategy,
        private StreamFactoryInterface $streamFactory,
        private ResponseFactoryInterface $responseFactory,
        UploadedFileFactoryInterface $uploadedFileFactory,
        ServerRequestFactoryInterface $serverRequestFactory,
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

    protected function shouldCloseConnection(ServerRequestInterface $request): bool
    {
        return $request->getProtocolVersion() === '1.0' || $request->getHeaderLine('Connection') === 'close';
    }

    protected function getPublicPathFile(ServerRequestInterface $request): string
    {
        $checkFile = "{$this->kernel->getProjectDir()}/public{$request->getUri()->getPath()}";
        $checkFile = str_replace('..', '/', $checkFile);

        return $checkFile;
    }

    protected function generateResponse(ResponseInterface $response): \Generator
    {
        $msg = 'HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase() . "\r\n";

        if ($response->getHeaderLine('Transfer-Encoding') === '' && $response->getHeaderLine('Content-Length') === '') {
            $msg .= 'Content-Length: ' . $response->getBody()->getSize() . "\r\n";
        }
        if ($response->getHeaderLine('Content-Type') === '') {
            $msg .= "Content-Type: text/html\r\n";
        }
        if ($response->getHeaderLine('Connection') === '') {
            $msg .= "Connection: keep-alive\r\n";
        }
        if ($response->getHeaderLine('Server') === '') {
            $msg .= "Server: workerman\r\n";
        }
        foreach ($response->getHeaders() as $name => $values) {
            $msg .= "$name: " . implode(', ', $values) . "\r\n";
        }

        yield "$msg\r\n";

        $response->getBody()->rewind();
        while (!$response->getBody()->eof()) {
            yield $response->getBody()->read(self::$chunkSize);
        }
        $response->getBody()->close();
    }
}
