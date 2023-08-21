<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Test;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WebserverResponseTest extends KernelTestCase
{
    public function testWebserverResponse(): void
    {
        $client = new Client([
            'http_errors' => false,
        ]);

        $response1 = $client->request('GET', 'http://127.0.0.1:8888/test');
        $response2 = $client->request('GET', 'http://127.0.0.1:8888/not_exist');

        $this->assertSame('hello from test controller', (string) $response1->getBody());
        $this->assertSame(200, $response1->getStatusCode());
        $this->assertSame(404, $response2->getStatusCode());
    }
}
