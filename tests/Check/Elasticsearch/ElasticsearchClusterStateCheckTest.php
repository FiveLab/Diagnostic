<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Namespaces\ClusterNamespace;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchClusterStateCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Result\Warning;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ElasticsearchClusterStateCheckTest extends AbstractElasticsearchTestCase
{
    /**
     * @var MockObject|Client
     */
    private $client;

    /**
     * @var ElasticsearchClusterStateCheck
     */
    private $check;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithElasticsearch()) {
            self::markTestSkipped('The ElasticSearch is not configured.');
        }

        $this->client = $this->createMock(Client::class);

        $ref = new \ReflectionClass(ElasticsearchClusterStateCheck::class);
        $this->check = $ref->newInstanceWithoutConstructor();

        $refClientProperty = $ref->getProperty('client');
        $refClientProperty->setAccessible(true);
        $refClientProperty->setValue($this->check, $this->client);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckGreen(): void
    {
        $clusterHealthMock = $this->createMock(ClusterNamespace::class);

        $clusterHealthMock->expects(self::any())
            ->method('health')
            ->willReturn($this->getHealthParamsWithStatus('green'));

        $this->client->expects(self::any())
            ->method('cluster')
            ->willReturn($clusterHealthMock);

        $result = $this->check->check();

        self::assertEquals(new Success('Cluster status is GREEN.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckRed(): void
    {
        $clusterHealthMock = $this->createMock(ClusterNamespace::class);

        $clusterHealthMock->expects(self::any())
            ->method('health')
            ->willReturn($this->getHealthParamsWithStatus('red'));

        $this->client->expects(self::any())
            ->method('cluster')
            ->willReturn($clusterHealthMock);

        $result = $this->check->check();

        self::assertEquals(new Failure('Cluster status is RED. Please check the logs.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckYellow(): void
    {
        $clusterHealthMock = $this->createMock(ClusterNamespace::class);

        $clusterHealthMock->expects(self::any())
            ->method('health')
            ->willReturn($this->getHealthParamsWithStatus('yellow'));

        $this->client->expects(self::any())
            ->method('cluster')
            ->willReturn($clusterHealthMock);

        $result = $this->check->check();

        self::assertEquals(new Warning('Cluster status is YELLOW. Please check the logs.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckIfStatusIsUnknown(): void
    {
        $clusterHealthMock = $this->createMock(ClusterNamespace::class);

        $clusterHealthMock->expects(self::any())
            ->method('health')
            ->willReturn($this->getHealthParamsWithStatus('some'));

        $this->client->expects(self::any())
            ->method('cluster')
            ->willReturn($clusterHealthMock);

        $result = $this->check->check();

        self::assertEquals(new Failure('Cluster status is undefined. Please check the logs.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckIfStatusIsMissed(): void
    {
        $clusterHealthMock = $this->createMock(ClusterNamespace::class);

        $clusterHealthMock->expects(self::any())
            ->method('health')
            ->willReturn([]);

        $this->client->expects(self::any())
            ->method('cluster')
            ->willReturn($clusterHealthMock);

        $result = $this->check->check();

        self::assertEquals(new Failure('Cluster status is undefined. Please check the logs.'), $result);
    }

    /**
     * Get health params with status
     *
     * @param string $status
     *
     * @return array
     */
    private function getHealthParamsWithStatus(string $status): array
    {
        return [
            'status' => $status,
        ];
    }
}
