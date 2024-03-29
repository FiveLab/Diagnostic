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

use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;

/**
 * Helper for create elasticsearch check instances.
 */
abstract class AbstractElasticsearchCheck implements CheckInterface
{
    /**
     * @var ElasticsearchClientBuilder|OpenSearchClientBuilder
     */
    private readonly ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder;

    /**
     * @var null|ElasticsearchClient|OpenSearchClient
     */
    private ElasticsearchClient|OpenSearchClient|null $client = null;

    /**
     * Constructor.
     *
     * @param ElasticsearchConnectionParameters                       $connectionParameters
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder|null $clientBuilder
     *
     * @throws \RuntimeException
     */
    public function __construct(private readonly ElasticsearchConnectionParameters $connectionParameters, ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder = null)
    {
        if (null === $clientBuilder) {
            if (!\class_exists(ElasticsearchClientBuilder::class)) {
                throw new \RuntimeException('The package "elasticsearch/elasticsearch" is not installed.');
            }

            $clientBuilder = ElasticsearchClientBuilder::create();
        }

        $this->clientBuilder = $clientBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return $this->convertConnectionParametersToArray();
    }

    /**
     * Get engine name
     *
     * @return string
     */
    public function getEngineName(): string
    {
        if ($this->clientBuilder instanceof ElasticsearchClientBuilder) {
            return 'Elasticsearch';
        }

        if ($this->clientBuilder instanceof OpenSearchClientBuilder) {
            return 'OpenSearch';
        }

        // @phpstan-ignore-next-line
        throw new \RuntimeException(\sprintf(
            'ClientBuilder %s is not supported. Supports only %s',
            (new \ReflectionClass($this->clientBuilder))->getName(),
            \implode(' or ', [ElasticsearchClientBuilder::class, OpenSearchClientBuilder::class])
        ));
    }

    /**
     * Create client
     *
     * @return ElasticsearchClient|OpenSearchClient
     */
    protected function createClient(): ElasticsearchClient|OpenSearchClient
    {
        if (null === $this->client) {
            $this->client = $this->clientBuilder
                ->setHosts([$this->connectionParameters->getDsn()])
                ->build();
        }

        return $this->client;
    }

    /**
     * Convert elastic search connection parameters to array for view after check.
     *
     * @return array<string, string|int>
     */
    protected function convertConnectionParametersToArray(): array
    {
        $params = [
            'host' => $this->connectionParameters->host,
            'port' => $this->connectionParameters->port,
            'ssl'  => $this->connectionParameters->ssl ? 'yes' : 'no',
        ];

        if ($this->connectionParameters->username || $this->connectionParameters->password) {
            $params['user'] = $this->connectionParameters->username ?: '(null)';
            $params['pass'] = '***';
        }

        return $params;
    }
}
