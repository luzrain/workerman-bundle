<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Test;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ResponseTest extends KernelTestCase
{
    public function testController(): void
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request('GET', 'http://127.0.0.1:8888/response_test');

        $this->assertSame('hello from test controller', (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testNotFound(): void
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request('GET', 'http://127.0.0.1:8888/response_test_not_exist');

        $this->assertSame(404, $response->getStatusCode());
    }
}
