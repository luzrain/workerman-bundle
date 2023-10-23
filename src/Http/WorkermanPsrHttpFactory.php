<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Http;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Workerman\Protocols\Http\Request;

final class WorkermanPsrHttpFactory
{
    public function __construct(
        private ServerRequestFactoryInterface $serverRequestFactory,
        private StreamFactoryInterface $streamFactory,
        private UploadedFileFactoryInterface $uploadedFileFactory,
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
            ->withUploadedFiles($this->normalizeFiles($workermanRequest->file()))
        ;
    }

    public function normalizeFiles(array $files)
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = $this->createUploadedFileFromSpec($value);
            } elseif (is_array($value)) {
                $normalized[$key] = $this->normalizeFiles($value);
                continue;
            } else {
                throw new \InvalidArgumentException('Invalid value in files specification');
            }
        }

        return $normalized;
    }

    private function createUploadedFileFromSpec(array $value)
    {
        if (is_array($value['tmp_name'])) {
            return $this->normalizeNestedFileSpec($value);
        }

        return $this->uploadedFileFactory->createUploadedFile(
            $this->streamFactory->createStreamFromFile($value['tmp_name']),
            (int) $value['size'],
            (int) $value['error'],
            $value['name'],
            $value['type'],
        );
    }

    private function normalizeNestedFileSpec(array $files = [])
    {
        $normalizedFiles = [];

        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            ];
            $normalizedFiles[$key] = $this->createUploadedFileFromSpec($spec);
        }

        return $normalizedFiles;
    }
}
