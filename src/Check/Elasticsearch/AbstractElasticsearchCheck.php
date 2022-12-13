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

use Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;

/**
 * Helper for create elasticsearch check instances.
 */
abstract class AbstractElasticsearchCheck
{
    /**
     * @var ElasticsearchClientBuilder|OpenSearchClientBuilder
     */
    protected $clientBuilder;

    /**
     * @var ElasticsearchConnectionParameters
     */
    protected ElasticsearchConnectionParameters $connectionParameters;

    /**
     * Constructor.
     *
     * @param mixed                             $clientBuilder
     * @param ElasticsearchConnectionParameters $connectionParameters
     *
     * @throws \RuntimeException
     */
    public function __construct($clientBuilder, ElasticsearchConnectionParameters $connectionParameters)
    {
        if (!($clientBuilder instanceof ElasticsearchClientBuilder || $clientBuilder instanceof OpenSearchClientBuilder)) {
            throw new \RuntimeException(\sprintf(
                'ClientBuilder must be one of: %s',
                \implode(' or ', [ElasticsearchClientBuilder::class, OpenSearchClientBuilder::class])
            ));
        }

        $this->clientBuilder = $clientBuilder;
        $this->connectionParameters = $connectionParameters;
    }

    /**
     * Convert elastic search connection parameters to array for view after check.
     *
     * @return array<string, string|int>
     */
    public function convertConnectionParametersToArray(): array
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
    }
}
