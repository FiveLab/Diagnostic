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

namespace FiveLab\Component\Diagnostic\Check\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Warning;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 *  Checks ES cluster state via _cluster/health endpoint
 */
class ElasticsearchClusterStateCheck implements CheckInterface
{
    /**
     * @var ElasticsearchConnectionParameters
     */
    private ElasticsearchConnectionParameters $connectionParameters;

    /**
     * @var Client|null
     */
    private ?Client $client = null;

    /**
     * Constructor.
     *
     * @param ElasticsearchConnectionParameters $connectionParameters
     */
    public function __construct(ElasticsearchConnectionParameters $connectionParameters)
    {
        $this->connectionParameters = $connectionParameters;
    }

    /**
     * @return ResultInterface
     */
    public function check(): ResultInterface
    {
        try {
            /** @var Client $client */
            $client = $this->createClient();

            $client->ping();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to ElasticSearch: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        try {
            $healthStatus = $client
                ->cluster()
                ->health();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Failed to get health status of the cluster : %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        return $this->parseClusterStatus($healthStatus);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return ElasticsearchHelper::convertConnectionParametersToArray($this->connectionParameters);
    }

    /**
     * Create client
     *
     * @return Client|Failure
     */
    private function createClient()
    {
        if (!\class_exists(Client::class)) {
            return new Failure('The package "elasticsearch/elasticsearch" is not installed.');
        }

        if ($this->client) {
            return $this->client;
        }

        $this->client = ClientBuilder::create()
            ->setHosts([$this->connectionParameters->getDsn()])
            ->build();

        return $this->client;
    }

    /**
     * Parse cluster status
     *
     * @param array<string> $responseParams
     *
     * @return ResultInterface
     */
    private function parseClusterStatus(array $responseParams): ResultInterface
    {
        $default =  new Failure('Cluster status is undefined. Please check the logs.');

        if (\array_key_exists('status', $responseParams)) {
            switch ($responseParams['status']) {
                case 'green':
                    return new Success('Cluster status is GREEN.');

                case 'yellow':
                    return new Warning('Cluster status is YELLOW. Please check the logs.');

                case 'red':
                    return new Failure('Cluster status is RED. Please check the logs.');

                default:
                    return $default;
            }
        }

        return $default;
    }
}
