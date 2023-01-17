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
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;

/**
 * Helper for create elasticsearch check instances.
 */
abstract class AbstractElasticsearchCheck
{
    /**
     * @var ElasticsearchConnectionParameters
     */
    protected ElasticsearchConnectionParameters $connectionParameters;

    /**
     * @var ElasticsearchClientBuilder|OpenSearchClientBuilder
     */
    private $clientBuilder;

    /**
     * @var null|ElasticsearchClient|OpenSearchClient
     */
    private $client;

    /**
     * Constructor.
     *
     * @param ElasticsearchConnectionParameters $connectionParameters
     * @param mixed                             $clientBuilder
     *
     * @throws \RuntimeException
     */
    public function __construct(ElasticsearchConnectionParameters $connectionParameters, $clientBuilder = null)
    {
        if (null === $clientBuilder) {
            if (!\class_exists(ElasticsearchClientBuilder::class)) {
                throw new \RuntimeException('The package "elasticsearch/elasticsearch" is not installed.');
            }

            $clientBuilder = ElasticsearchClientBuilder::create();
        }

        if (!$clientBuilder instanceof ElasticsearchClientBuilder && !$clientBuilder instanceof OpenSearchClientBuilder) {
            throw new \RuntimeException(\sprintf(
                'ClientBuilder must be one of: %s',
                \implode(' or ', [ElasticsearchClientBuilder::class, OpenSearchClientBuilder::class])
            ));
        }

        $this->connectionParameters = $connectionParameters;
        $this->clientBuilder = $clientBuilder;
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
    protected function createClient()
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
            'host' => $this->connectionParameters->getHost(),
            'port' => $this->connectionParameters->getPort(),
            'ssl'  => $this->connectionParameters->isSsl() ? 'yes' : 'no',
        ];

        if ($this->connectionParameters->getUsername() || $this->connectionParameters->getPassword()) {
            $params['user'] = $this->connectionParameters->getUsername() ?: '(null)';
            $params['pass'] = '***';
        }

        return $params;
    }
}
