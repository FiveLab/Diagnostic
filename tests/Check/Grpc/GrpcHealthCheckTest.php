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

namespace FiveLab\Component\Diagnostic\Tests\Check\Grpc;

use Elasticsearch\Client;
use Elasticsearch\Namespaces\ClusterNamespace;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchClusterStateCheck;
use FiveLab\Component\Diagnostic\Check\Grpc\GrpcHealthCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Result\Warning;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GrpcHealthCheckTest extends TestCase
{
    /**
     * @test
     *
     * @param string $hostname
     * @param string $service
     * @param ResultInterface $expectedResult
     *
     * @return void
     *
     * @dataProvider provideCheckRequests
     */
    public function testCheck(string $hostname, string $service, ResultInterface $expectedResult): void
    {
        $this->assertEquals((new GrpcHealthCheck($hostname, $service))->check(), $expectedResult);
    }

    /**
     * @test
     *
     * @return void
     */
    public function testGetExtraParameters(): void
    {
        $this->assertEquals(
            [
                'hostname' => 'server:5000',
                'service' => 'some.Service',
            ],
            (new GrpcHealthCheck('server:5000', 'some.Service'))->getExtraParameters()
        );
    }

    /**
     * Provide check requests
     *
     * @return array
     */
    public function provideCheckRequests(): array
    {
        return [
            'success' => [
                'diagnostic-grpc-server:50051',
                'GreetService',
                new Success('Successful grpc health-check.'),
            ],
            'service unknown' => [
                'diagnostic-grpc-server:50051',
                'service.Unknown',
                new Failure('Grpc health-check failed: \'service.Unknown\' status is \'SERVICE_UNKNOWN\'.'),
            ],
            'connection failed' => [
                'unknown:2222',
                'GreetService',
                new Failure('Grpc health-check failed: DNS resolution failed for unknown:2222: UNKNOWN: Name or service not known.'),
            ],
        ];
    }
}