<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Http;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Workerman\Protocols\Http\Request;

final class WorkermanPsrHttpFactory
{
    public function __construct(
        private ServerRequestFactoryInterface $serverRequestFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function createRequest(Request $workermanRequest): ServerRequestInterface
    {
        $psrRequest = $this->serverRequestFactory->createServerRequest(
            method: $workermanRequest->method(),
            uri: $workermanRequest->uri(),
            serverParams: $_SERVER + [
                'REMOTE_ADDR' => $workermanRequest->connection->getRemoteIp(),
            ],
        );

        foreach ($workermanRequest->header() as $name => $value) {
            try {
                $psrRequest = $psrRequest->withHeader($name, $value);
            } catch (\InvalidArgumentException) {
                // ignore invalid header
            }
        }

        return $psrRequest
            ->withProtocolVersion($workermanRequest->protocolVersion())
            ->withBody($this->streamFactory->createStream($workermanRequest->rawBody()))
            ->withCookieParams($workermanRequest->cookie())
            ->withQueryParams($workermanRequest->get())
            ->withParsedBody($workermanRequest->post())
            ->withUploadedFiles($workermanRequest->file())
        ;
    }
}
