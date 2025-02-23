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

use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;
use Elasticsearch\Namespaces\ClusterNamespace as ElasticsearchClusterNamespace;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchClusterStateCheck;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Result\Warning;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;
use OpenSearch\Namespaces\ClusterNamespace as OpenSearchClusterNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ElasticsearchClusterStateCheckTest extends AbstractElasticsearchTestCase
{
    #[Test]
    #[DataProvider('clusterStateCheckProvider')]
    public function shouldSuccessCheckGreen(ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder, ElasticsearchConnectionParameters $connectionParameters, string $clientClass, string $clusterNamespaceClass): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $clusterHealthMock = $this->createMock($clusterNamespaceClass);

        $clusterHealthMock->expects(self::any())
            ->method('health')
            ->willReturn($this->getHealthParamsWithStatus('green'));

        $client = $this->createMock($clientClass);

        $client->expects(self::any())
            ->method('cluster')
            ->willReturn($clusterHealthMock);

        $mockedBuilder = $this->createMock((new \ReflectionClass($clientBuilder))->getName());

        $mockedBuilder->expects(self::any())
            ->method('setHosts')
            ->willReturn($mockedBuilder);

        $mockedBuilder->expects(self::any())
            ->method('build')
            ->willReturn($client);

        $check = new ElasticsearchClusterStateCheck($connectionParameters, $mockedBuilder);

        $result = $check->check();

        self::assertEquals(new Success('Cluster status is GREEN.'), $result);
    }

    #[Test]
    #[DataProvider('clusterStateCheckProvider')]
    public function shouldSuccessCheckRed(ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder, ElasticsearchConnectionParameters $connectionParameters, string$clientClass, string $clusterNamespaceClass): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $clusterHealthMock = $this->createMock($clusterNamespaceClass);

        $clusterHealthMock->expects(self::any())
            ->method('health')
            ->willReturn($this->getHealthParamsWithStatus('red'));

        $client = $this->createMock($clientClass);

        $client->expects(self::any())
            ->method('cluster')
            ->willReturn($clusterHealthMock);

        $mockedBuilder = $this->createMock((new \ReflectionClass($clientBuilder))->getName());

        $mockedBuilder->expects(self::any())
            ->method('setHosts')
            ->willReturn($mockedBuilder);

        $mockedBuilder->expects(self::any())
            ->method('build')
            ->willReturn($client);

        $check = new ElasticsearchClusterStateCheck($connectionParameters, $mockedBuilder);

        $result = $check->check();

        self::assertEquals(new Failure('Cluster status is RED. Please check the logs.'), $result);
    }

    #[Test]
    #[DataProvider('clusterStateCheckProvider')]
    public function shouldSuccessCheckYellow(ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder, ElasticsearchConnectionParameters $connectionParameters, string$clientClass, string $clusterNamespaceClass): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $clusterHealthMock = $this->createMock($clusterNamespaceClass);

        $clusterHealthMock->expects(self::any())
            ->method('health')
            ->willReturn($this->getHealthParamsWithStatus('yellow'));

        $client = $this->createMock($clientClass);

        $client->expects(self::any())
            ->method('cluster')
            ->willReturn($clusterHealthMock);

        $mockedBuilder = $this->createMock((new \ReflectionClass($clientBuilder))->getName());

        $mockedBuilder->expects(self::any())
            ->method('setHosts')
            ->willReturn($mockedBuilder);

        $mockedBuilder->expects(self::any())
            ->method('build')
            ->willReturn($client);

        $check = new ElasticsearchClusterStateCheck($connectionParameters, $mockedBuilder);

        $result = $check->check();

        self::assertEquals(new Warning('Cluster status is YELLOW. Please check the logs.'), $result);
    }

    #[Test]
    #[DataProvider('clusterStateCheckProvider')]
    public function shouldFailCheckIfStatusIsUnknown(ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder, ElasticsearchConnectionParameters $connectionParameters, string$clientClass, string $clusterNamespaceClass): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $clusterHealthMock = $this->createMock($clusterNamespaceClass);

        $clusterHealthMock->expects(self::any())
            ->method('health')
            ->willReturn($this->getHealthParamsWithStatus('some'));

        $client = $this->createMock($clientClass);

        $client->expects(self::any())
            ->method('cluster')
            ->willReturn($clusterHealthMock);

        $mockedBuilder = $this->createMock((new \ReflectionClass($clientBuilder))->getName());

        $mockedBuilder->expects(self::any())
            ->method('setHosts')
            ->willReturn($mockedBuilder);

        $mockedBuilder->expects(self::any())
            ->method('build')
            ->willReturn($client);

        $check = new ElasticsearchClusterStateCheck($connectionParameters, $mockedBuilder);

        $result = $check->check();

        self::assertEquals(new Failure('Cluster status is undefined. Please check the logs.'), $result);
    }

    #[Test]
    #[DataProvider('clusterStateCheckProvider')]
    public function shouldFailCheckIfStatusIsMissed(ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder, ElasticsearchConnectionParameters $connectionParameters, string$clientClass, string $clusterNamespaceClass): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $clusterHealthMock = $this->createMock($clusterNamespaceClass);

        $clusterHealthMock->expects(self::any())
            ->method('health')
            ->willReturn([]);

        $client = $this->createMock($clientClass);

        $client->expects(self::any())
            ->method('cluster')
            ->willReturn($clusterHealthMock);

        $mockedBuilder = $this->createMock((new \ReflectionClass($clientBuilder))->getName());

        $mockedBuilder->expects(self::any())
            ->method('setHosts')
            ->willReturn($mockedBuilder);

        $mockedBuilder->expects(self::any())
            ->method('build')
            ->willReturn($client);

        $check = new ElasticsearchClusterStateCheck($connectionParameters, $mockedBuilder);

        $result = $check->check();

        self::assertEquals(new Failure('Cluster status is undefined. Please check the logs.'), $result);
    }

    public static function clusterStateCheckProvider(): array
    {
        return [
            [ElasticsearchClientBuilder::create(), self::getElasticsearchConnectionParameters(), ElasticsearchClient::class, ElasticsearchClusterNamespace::class],
            [OpenSearchClientBuilder::create(), self::getOpenSearchConnectionParameters(), OpenSearchClient::class, OpenSearchClusterNamespace::class],
        ];
    }

    private function getHealthParamsWithStatus(string $status): array
    {
        return [
            'status' => $status,
        ];
    }
}
