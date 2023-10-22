<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RequestParametersTest extends KernelTestCase
{
    public function testHeaders(): void
    {
        $response = $this->createResponse('GET', [
            'headers' => [
                'test-header-1' => 'test1',
            ],
        ]);

        $this->assertSame('test1', $response['headers']['test-header-1'][0] ?? null, 'Header "test-header-1" not found in Request object');
    }

    public function testGetParameters(): void
    {
        $response = $this->createResponse('GET', [
            'query' => [
                'test-query-2' => 'test2',
            ],
        ]);

        $this->assertSame('test2', $response['get']['test-query-2'] ?? null, 'GET param "test-query-2" not found in Request object');
    }

    public function testPostParameters(): void
    {
        $response = $this->createResponse('POST', [
            'form_params' => [
                'test-post-3' => 'test3',
            ],
        ]);

        $this->assertSame('test3', $response['post']['test-post-3'] ?? null, 'POST param "test-post-3" not found in Request object');
    }

    public function testCookiesParameters(): void
    {
        $response = $this->createResponse('POST', [
            'cookies' => CookieJar::fromArray(domain: '127.0.0.1', cookies: [
                'test-cookie-4' => 'test4',
            ]),
        ]);

        $this->assertSame('test4', $response['cookies']['test-cookie-4'] ?? null, 'COOKIE param "test-cookie-4" not found in Request object');
    }

    public function testFilesParameters(): void
    {
        $this->markTestIncomplete('guzzle do not send "boundary=" string in request, but workerman request requires it.');

        $response = $this->createResponse('POST', [
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
            'multipart' => [
                [
                    'name' => 'test-file-5',
                    'contents' => 'test-file-5-content',
                ],
            ],
        ]);
    }

    public function testRawRequest(): void
    {
        $response = $this->createResponse('POST', [
            'body' => 'test-raw-request-5',
        ]);

        $this->assertSame('test-raw-request-5', $response['raw_request'], 'Raw request not assert failed');
    }

    private function createResponse(string $method, array $options = []): array
    {
        $client = new Client(['http_errors' => false]);
        $response = $client->request($method, 'http://127.0.0.1:8888/request_test', $options);

        return json_decode((string) $response->getBody(), true);
    }
}
