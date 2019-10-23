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
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check success connect to ElasticSearch.
 *
 * Critical: the client library for ping request only check via HEAD method.
 *           As result if you connect to any services via ssl, check return success result.
 *           For fix this, please use additional check for check version of ElasticSearch (as an example).
 */
class ElasticsearchConnectionCheck implements CheckInterface
{
    /**
     * @var ElasticsearchConnectionParameters
     */
    private $connectionParameters;

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
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        if (!\class_exists(Client::class)) {
            return new Failure('The package "elasticsearch/elasticsearch" is not installed.');
        }

        try {
            $client = ClientBuilder::create()
                ->setHosts([$this->connectionParameters->getDsn()])
                ->build();

            $ping = $client->ping();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to ElasticSearch: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        if ($ping) {
            return new Success('Success connect to ElasticSearch and send ping request.');
        }

        return new Failure('Fail connect to ElasticSearch or send ping request.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
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
