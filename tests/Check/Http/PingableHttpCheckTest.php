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

use FiveLab\Component\Diagnostic\Check\Http\PingableHttpCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use GuzzleHttp\Psr7\BufferStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\TransferException;
use Http\Client\HttpClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PingableHttpCheckTest extends TestCase
{
    /**
     * @var HttpClient|MockObject
     */
    private $client;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->client = $this->createMock(HttpClient::class);
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
     * @param string            $expectedApplicationName
     * @param array             $expectedApplicationRoles
     * @param string|null       $expectedVersion
     *
     * @dataProvider provideData
     */
    public function shouldSuccessCheck(ResponseInterface $response, ResultInterface $expectedResult, string $method, string $url, array $headers, string $body, int $expectedStatusCode, string $expectedApplicationName, array $expectedApplicationRoles, string $expectedVersion = null): void
    {
        $expectedRequest = new Request($method, $url, $headers, $body);

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($response);

        $check = new PingableHttpCheck($method, $url, $headers, $body, $expectedStatusCode, $expectedApplicationName, $expectedApplicationRoles, $expectedVersion, $this->client, new GuzzleMessageFactory());

        $result = $check->check();

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckIfGuzzleThrowException(): void
    {
        $expectedRequest = new Request('GET', '/some', [], '');

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willThrowException(new TransferException('some'));

        $check = new PingableHttpCheck('GET', '/some', [], '', 200, 'some', [], '1.1', $this->client, new GuzzleMessageFactory());

        $result = $check->check();

        self::assertEquals(new Failure('Fail send HTTP request. Error: some.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParameters(): void
    {
        $check = new PingableHttpCheck('GET', '/some', ['Content-Type' => 'text/plain'], 'Foo Bar', 200, 'some', ['foo', 'bar'], '1.1', $this->client);

        self::assertEquals([
            'method'              => 'GET',
            'url'                 => '/some',
            'headers'             => '{"Content-Type":"text\/plain"}',
            'body'                => 'Foo Bar',
            'status code'         => 200,
            'application name'    => 'some',
            'application roles'   => 'foo, bar',
            'application version' => '1.1',
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldEncodeUriIfUriContainPassword(): void
    {
        $check = new PingableHttpCheck('GET', 'https://user:pass@domain.com/some?key=foo#frag', ['Content-Type' => 'text/plain'], '', 200, '', [], '', $this->client);

        self::assertEquals([
            'method'              => 'GET',
            'url'                 => 'https://user:***@domain.com/some?key=foo#frag',
            'headers'             => '{"Content-Type":"text\/plain"}',
            'body'                => '',
            'status code'         => 200,
            'application name'    => '',
            'application roles'   => '',
            'application version' => '',
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldSuccessEncodeAuthorizationHeader(): void
    {
        $check = new PingableHttpCheck('GET', 'https://domain.com/some', ['Authorization' => 'basic some-foo'], '', 200, '', [], '', $this->client);

        self::assertEquals([
            'method'              => 'GET',
            'url'                 => 'https://domain.com/some',
            'headers'             => '{"Authorization":"***"}',
            'body'                => '',
            'status code'         => 200,
            'application name'    => '',
            'application roles'   => '',
            'application version' => '',
        ], $check->getExtraParameters());
    }

    /**
     * Provide data
     *
     * @return array
     */
    public function provideData(): array
    {
        return [
            'success with only application name' => [
                new Response(200, [], $this->makeBufferWithJson('some', ['role1'])),
                new Success('Success get response and check all options.'),
                'GET',
                '/ping',
                [],
                '',
                200,
                'some',
                [],
            ],

            'success with roles' => [
                new Response(200, [], $this->makeBufferWithJson('some', ['role1', 'role2'])),
                new Success('Success get response and check all options.'),
                'GET',
                '/ping',
                [],
                '',
                200,
                'some',
                ['role1'],
            ],

            'success with version' => [
                new Response(200, [], $this->makeBufferWithJson('some', ['role1'], '2.1.1')),
                new Success('Success get response and check all options.'),
                'GET',
                '/ping',
                [],
                '',
                200,
                'some',
                ['role1'],
                '~2.0',
            ],

            'fail with status code' => [
                new Response(404, [], '404 Server Error'),
                new Failure('The server return "404" status code, but we expect "200" status code.'),
                'GET',
                '/ping',
                [],
                '',
                200,
                'some',
                [],
            ],

            'fail with empty response' => [
                new Response(200, [], ''),
                new Failure('Server returns empty response.'),
                'GET',
                '/ping',
                [],
                '',
                200,
                'some',
                [],
            ],

            'fail with invalid json' => [
                new Response(200, [], '<root><child></child></root>'),
                new Failure('Cannot decode the response to JSON. Error: Syntax error.'),
                'GET',
                '/ping',
                [],
                '',
                200,
                'some',
                [],
            ],

            'fail with application name' => [
                new Response(200, [], $this->makeBufferWithJson('some', ['role1'])),
                new Failure('The server return "some" application name, but we expect "foo-bar" application name.'),
                'GET',
                '/ping',
                [],
                '',
                200,
                'foo-bar',
                [],
            ],

            'fail with application roles' => [
                new Response(200, [], $this->makeBufferWithJson('some', ['role1'])),
                new Failure('Missed "role2" application roles.'),
                'GET',
                '/ping',
                [],
                '',
                200,
                'some',
                ['role1', 'role2'],
            ],

            'fail with version' => [
                new Response(200, [], $this->makeBufferWithJson('some', ['role1'], '1.0.15')),
                new Failure('The server return "1.0.15" version, but we expect "~1.1.0".'),
                'GET',
                '/ping',
                [],
                '',
                200,
                'some',
                ['role1'],
                '~1.1.0',
            ],
        ];
    }

    /**
     * Make a buffer
     *
     * @param string $applicationName
     * @param array  $roles
     * @param string $version
     *
     * @return StreamInterface
     */
    private function makeBufferWithJson(string $applicationName, array $roles, string $version = '1.0'): StreamInterface
    {
        $buffer = new BufferStream();

        $data = [
            'application' => $applicationName,
            'roles'       => $roles,
            'version'     => $version,
        ];

        $buffer->write(\json_encode($data));

        return $buffer;
    }
}
