<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace HyperfTest\Cases;

use Hyperf\Engine\Exception\HttpClientException;
use Hyperf\Engine\Http\Client;

/**
 * @internal
 * @coversNothing
 */
class ClientTest extends AbstractTestCase
{
    /**
     * @group Server
     */
    public function testClientRequest()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9501);
            $response = $client->request('GET', '/');
            $this->assertSame(200, $response->statusCode);
            $this->assertSame(['Hyperf'], $response->headers['server']);
            $this->assertSame('Hello World.', $response->body);
        });
    }

    /**
     * @group Server
     */
    public function testClientSocketConnectionRefused()
    {
        $this->runInCoroutine(function () {
            try {
                $client = new Client('127.0.0.1', 9502);
                $client->request('GET', '/timeout?time=1');
                $this->assertTrue(false);
            } catch (\Throwable $exception) {
                $this->assertInstanceOf(HttpClientException::class, $exception);
                $this->assertSame(SOCKET_ECONNREFUSED, $exception->getCode());
                $this->assertSame('Connection refused', $exception->getMessage());
            }
        });
    }

    /**
     * @group Server
     */
    public function testClientJsonRequest()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9501);
            $response = $client->request(
                'POST',
                '/',
                ['Content-Type' => 'application/json charset=UTF-8'],
                json_encode(['name' => 'Hyperf'], JSON_UNESCAPED_UNICODE)
            );
            $this->assertSame(200, $response->statusCode);
            $this->assertSame(['Hyperf'], $response->headers['server']);
            $this->assertSame('Hello World.', $response->body);
        });
    }

    /**
     * @group Server
     */
    public function testClientSocketConnectionTimeout()
    {
        $this->runInCoroutine(function () {
            try {
                $client = new Client('127.0.0.1', 9501);
                $client->set(['timeout' => 0.1]);
                $client->request('GET', '/timeout?time=1');
                $this->assertTrue(false);
            } catch (\Throwable $exception) {
                $this->assertInstanceOf(HttpClientException::class, $exception);
                $this->assertSame(SOCKET_ETIMEDOUT, $exception->getCode());
                $this->assertStringContainsString('timed out', $exception->getMessage());
            }
        });
    }

    /**
     * @group Server
     */
    public function testClientCookies()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9501);
            $response = $client->request('GET', '/cookies');
            $this->assertSame(200, $response->statusCode);
            $this->assertSame(['Hyperf'], $response->headers['server']);
            $this->assertSame([
                'X-Server-Id=' . $response->body,
                'X-Server-Name=Hyperf',
            ], $response->headers['Set-Cookie']);
        });
    }

    /**
     * @group Server
     */
    public function testClientNotFound()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9501);
            $response = $client->request('GET', '/not_found');
            $this->assertSame(404, $response->statusCode);
        });
    }
}
