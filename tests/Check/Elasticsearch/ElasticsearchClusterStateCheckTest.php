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

namespace FiveLab\Component\Diagnostic\Tests\Check\Elasticsearch;

use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchClusterStateCheck;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Result\Warning;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ElasticsearchClusterStateCheckTest extends AbstractElasticsearchTestCase
{
    #[Test]
    #[DataProvider('provideClusterStatuses')]
    public function shouldSuccessCheckGreen(?string $status, Result $expectedResult): void
    {
        $adapter = $this->createMock(HttpAdapterInterface::class);
        $params = ElasticsearchConnectionParameters::fromDsn('http://elasticsearch.local');

        $healthRequest = new Request('GET', 'http://elasticsearch.local/_cat/health');

        $adapter->expects($this->once())
            ->method('createRequest')
            ->with('GET', 'http://elasticsearch.local:9200/_cat/health', ['accept' => 'application/json'])
            ->willReturn($healthRequest);

        $adapter->expects($this->once())
            ->method('sendRequest')
            ->with($healthRequest)
            ->willReturn(new Response(body: \json_encode([
                ['status' => $status],
            ], JSON_THROW_ON_ERROR)));

        $check = new ElasticsearchClusterStateCheck($params, $adapter);

        $result = $check->check();

        self::assertEquals($expectedResult, $result);
    }

    #[Test]
    public function shouldSuccessGetExtraParameters(): void
    {
        $connectionParameters = ElasticsearchConnectionParameters::fromDsn('http://user:pass@host:9201');
        $check = new ElasticsearchClusterStateCheck($connectionParameters);

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'dsn' => 'http://user:***@host:9201',
        ], $parameters);
    }

    public static function provideClusterStatuses(): array
    {
        return [
            ['green', new Success('Cluster status is GREEN.')],
            ['yellow', new Warning('Cluster status is YELLOW.')],
            ['red', new Failure('Cluster status is RED.')],
            ['bla', new Failure('Unknown cluster status "bla".')],
            [null, new Failure('Fail connect to Elasticsearch/Opensearch - missed status in _cat/health.')],
        ];
    }
}
