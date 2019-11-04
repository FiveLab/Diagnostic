<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check\Http;

use FiveLab\Component\Diagnostic\Check\Http\HttpCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class HttpCheckTest extends TestCase
{
    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
    }

    /**
     * @test
     *
     * @param ResponseInterface $response
     * @param ResultInterface   $expectedResult
     * @param string            $method
     * @param string            $url
     * @param array             $headers
     * @param string            $body
     * @param int               $expectedStatusCode
     * @param string|null       $expectedBody
     *
     * @dataProvider provideDataForCheck
     */
    public function shouldSuccessCheck(ResponseInterface $response, ResultInterface $expectedResult, string $method, string $url, array $headers, string $body, int $expectedStatusCode, string $expectedBody = null): void
    {
        $expectedRequest = new Request($method, $url, $headers, $body);

        $this->client->expects(self::once())
            ->method('send')
            ->with($expectedRequest, [
                RequestOptions::TIMEOUT     => 5,
                RequestOptions::HTTP_ERRORS => false,
            ])
            ->willReturn($response);

        $check = new HttpCheck($method, $url, $headers, $body, $expectedStatusCode, $expectedBody, $this->client);

        $result = $check->check();

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @test
     */
    public function shouldFailIfClientThrowException()
    {
        $expectedRequest = new Request('GET', '/some');

        $this->client->expects(self::once())
            ->method('send')
            ->with($expectedRequest)
            ->willThrowException(new TransferException('some'));

        $check = new HttpCheck('GET', '/some', [], '', 200, null, $this->client);

        $result = $check->check();

        self::assertEquals(new Failure('Fail send HTTP request. Error: some.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetParameters()
    {
        $expectedRequest = new Request('GET', '/some');

        $this->client->expects(self::once())
            ->method('send')
            ->with($expectedRequest)
            ->willReturn(new Response(200));

        $check = new HttpCheck('GET', '/some', [], '', 200, null, $this->client);

        $check->check();

        self::assertEquals([
            'method'      => 'GET',
            'url'         => '/some',
            'headers'     => '[]',
            'body'        => '',
            'status code' => 200,
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldSuccessGetParametersWithBody()
    {
        $expectedRequest = new Request('GET', '/some');

        $this->client->expects(self::once())
            ->method('send')
            ->with($expectedRequest)
            ->willReturn(new Response(200, [], '{"result": "ok"}'));

        $check = new HttpCheck('GET', '/some', [], '', 200, '{"result": "ok"}', $this->client);

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

    /**
     * Provide data for check
     *
     * @return array
     */
    public function provideDataForCheck(): array
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
