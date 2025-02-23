<?php

/*
 * This file is part of the FiveLab Diagnostic package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check\Http;

use FiveLab\Component\Diagnostic\Check\Http\HttpCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\TransferException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

class HttpCheckTest extends TestCase
{
    private ClientInterface $client;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
    }

    #[Test]
    #[DataProvider('provideDataForCheck')]
    public function shouldSuccessCheck(ResponseInterface $response, Result $expectedResult, string $method, string $url, array $headers, string $body, int $expectedStatusCode, ?string $expectedBody = null): void
    {
        $expectedRequest = new Request($method, $url, $headers, $body);

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($response);

        $check = new HttpCheck($method, $url, $headers, $body, $expectedStatusCode, $expectedBody, new HttpAdapter($this->client));

        $result = $check->check();

        self::assertEquals($expectedResult, $result);
    }

    #[Test]
    public function shouldFailIfClientThrowException(): void
    {
        $expectedRequest = new Request('GET', '/some');

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willThrowException(new TransferException('some'));

        $check = new HttpCheck('GET', '/some', [], '', 200, null, new HttpAdapter($this->client));

        $result = $check->check();

        self::assertEquals(new Failure('Fail send HTTP request. Error: some.'), $result);
    }

    #[Test]
    public function shouldSuccessGetParameters(): void
    {
        $expectedRequest = new Request('GET', '/some');

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn(new Response(200));

        $check = new HttpCheck('GET', '/some', [], '', 200, null, new HttpAdapter($this->client));

        $check->check();

        self::assertEquals([
            'method'      => 'GET',
            'url'         => '/some',
            'headers'     => '[]',
            'body'        => '',
            'status code' => 200,
        ], $check->getExtraParameters());
    }

    #[Test]
    public function shouldSuccessGetParametersWithBody(): void
    {
        $expectedRequest = new Request('GET', '/some');

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn(new Response(200, [], '{"result": "ok"}'));

        $check = new HttpCheck('GET', '/some', [], '', 200, '{"result": "ok"}', new HttpAdapter($this->client));

        $check->check();

        self::assertEquals([
            'method'        => 'GET',
            'url'           => '/some',
            'headers'       => '[]',
            'body'          => '',
            'status code'   => 200,
            'expected body' => '{"result": "ok"}',
            'actual body'   => '{"result": "ok"}',
        ], $check->getExtraParameters());
    }

    public static function provideDataForCheck(): array
    {
        return [
            'success with check only status code' => [
                new Response(200, [], '{"result":"ok"}'),
                new Success('Success get response and check all options.'),
                'GET',
                '/some/foo',
                [],
                '',
                200,
                null,
            ],

            'success with check status code and body' => [
                new Response(404, [], '{"result": "not found"}'),
                new Success('Success get response and check all options.'),
                'GET',
                '/some/foo',
                [],
                '',
                404,
                '{"result": "not found"}',
            ],

            'fail if status code invalid' => [
                new Response(404),
                new Failure('The server return "404" status code, but we expect "200" status code.'),
                'GET',
                '/foo/bar',
                [],
                '',
                200,
            ],

            'fail if body is invalid' => [
                new Response(200, [], '{"result": "ok"}'),
                new Failure('The server return wrong response. We expect another content.'),
                'GET',
                '/foo',
                [],
                '',
                200,
                '<root><result>fail</result></root>',
            ],
        ];
    }
}
