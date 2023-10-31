<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Http;

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Luzrain\WorkermanBundle\Reboot\Strategy\RebootStrategyInterface;
use Luzrain\WorkermanBundle\Utils;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

final class HttpRequestHandler
{
    public function __construct(
        private KernelInterface $kernel,
        private StreamFactoryInterface $streamFactory,
        private ResponseFactoryInterface $responseFactory,
        private RebootStrategyInterface $rebootStrategy,
        private HttpMessageFactoryInterface $psrHttpFactory,
        private HttpFoundationFactoryInterface $httpFoundationFactory,
        private WorkermanHttpMessageFactory $workermanHttpFactory,
        private int $chunkSize,
    ) {
    }

    public function __invoke(TcpConnection $connection, Request $workermanRequest): void
    {
        if (PHP_VERSION_ID >= 80200) {
            \memory_reset_peak_usage();
        }

        $psrRequest = $this->workermanHttpFactory->createRequest($workermanRequest);
        $shouldCloseConnection = $psrRequest->getProtocolVersion() === '1.0' || $psrRequest->getHeaderLine('Connection') === 'close';

        if (\is_file($file = $this->getPublicPathFile($psrRequest))) {
            $this->createfileResponse($connection, $shouldCloseConnection, $file);
        } else {
            $this->createApplicationResponse($connection, $shouldCloseConnection, $psrRequest);
        }
    }

    private function createfileResponse(TcpConnection $connection, bool $shouldCloseConnection, string $file): void
    {
        $mimeTypedetector = new FinfoMimeTypeDetector();
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', $mimeTypedetector->detectMimeTypeFromPath($file))
            ->withBody($this->streamFactory->createStreamFromFile($file));

        foreach ($this->generateResponse($response) as $chunk) {
            $connection->send($chunk, true);
        }

        if ($shouldCloseConnection) {
            $connection->close();
        }
    }

    private function createApplicationResponse(TcpConnection $connection, bool $shouldCloseConnection, ServerRequestInterface $psrRequest): void
    {
        $this->kernel->boot();

        $symfonyRequest = $this->httpFoundationFactory->createRequest($psrRequest);
        $symfonyResponse = $this->kernel->handle($symfonyRequest);
        $sprResponse = $this->psrHttpFactory->createResponse($symfonyResponse);

        if ($shouldCloseConnection) {
            $sprResponse = $sprResponse->withAddedHeader('Connection', 'close');
        }

        foreach ($this->generateResponse($sprResponse) as $chunk) {
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

    private function getPublicPathFile(ServerRequestInterface $request): string
    {
        $checkFile = "{$this->kernel->getProjectDir()}/public{$request->getUri()->getPath()}";
        $checkFile = str_replace('..', '/', $checkFile);

        return $checkFile;
    }

    private function generateResponse(ResponseInterface $response): \Generator
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
            yield $response->getBody()->read($this->chunkSize);
        }
        $response->getBody()->close();
    }
}
