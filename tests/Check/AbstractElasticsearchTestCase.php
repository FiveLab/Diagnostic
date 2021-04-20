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

namespace FiveLab\Component\Diagnostic\Tests\Check;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use PHPUnit\Framework\TestCase;

abstract class AbstractElasticsearchTestCase extends TestCase
{
    /**
     * Get the elasticsearch host
     *
     * @return string|null
     */
    protected function getElasticsearchHost(): ?string
    {
        return \getenv('ELASTICSEARCH_HOST') ?: null;
    }

    /**
     * Get elasticsearch port
     *
     * @return int|null
     */
    protected function getElasticsearchPort(): int
    {
        return \getenv('ELASTICSEARCH_PORT') ? (int) \getenv('ELASTICSEARCH_PORT') : 9200;
    }

    /**
     * Get the user for connect to elasticsearch
     *
     * @return string
     */
    protected function getElasticsearchUser(): ?string
    {
        return \getenv('ELASTICSEARCH_USER') ? \getenv('ELASTICSEARCH_USER') : null;
    }

    /**
     * Get the password for connect to elasticsearch
     *
     * @return string
     */
    protected function getElasticsearchPassword(): ?string
    {
        return \getenv('ELASTICSEARCH_PASSWORD') ? \getenv('ELASTICSEARCH_PASSWORD') : null;
    }

    /**
     * Is use SSL for connect to elasticsearch?
     *
     * @return bool
     */
    protected function isElasticsearchSsl(): bool
    {
        return \getenv('ELASTICSEARCH_SSL') ? true : false;
    }

    /**
     * Can testing with elasticsearch?
     *
     * @return bool
     */
    protected function canTestingWithElasticsearch(): bool
    {
        return $this->getElasticsearchHost() && $this->getElasticsearchPort();
    }

    /**
     * Get connection parameters to elasticsearch
     *
     * @return ElasticsearchConnectionParameters
     */
    protected function getConnectionParameters(): ElasticsearchConnectionParameters
    {
        return new ElasticsearchConnectionParameters(
            $this->getElasticsearchHost(),
            $this->getElasticsearchPort(),
            $this->getElasticsearchUser(),
            $this->getElasticsearchPassword(),
            $this->isElasticsearchSsl()
        );
    }

    /**
     * Create a client
     *
     * @return Client
     */
    protected function createClient(): Client
    {
        $connectionParameters = $this->getConnectionParameters();

        return ClientBuilder::create()
            ->setHosts([$connectionParameters->getDsn()])
            ->build();
    }
}
