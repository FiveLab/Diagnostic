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

use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;
use PHPUnit\Framework\TestCase;

abstract class AbstractElasticsearchTestCase extends TestCase
{
    /**
     * Get the elasticsearch host
     *
     * @return string|null
     */
    protected static function getElasticsearchHost(): ?string
    {
        return \getenv('ELASTICSEARCH_HOST') ?: null;
    }

    /**
     * Get elasticsearch port
     *
     * @return int|null
     */
    protected static function getElasticsearchPort(): int
    {
        return \getenv('ELASTICSEARCH_PORT') ? (int) \getenv('ELASTICSEARCH_PORT') : 9200;
    }

    /**
     * Get the user for connect to elasticsearch
     *
     * @return string
     */
    protected static function getElasticsearchUser(): ?string
    {
        return \getenv('ELASTICSEARCH_USER') ? \getenv('ELASTICSEARCH_USER') : null;
    }

    /**
     * Get the password for connect to elasticsearch
     *
     * @return string
     */
    protected static function getElasticsearchPassword(): ?string
    {
        return \getenv('ELASTICSEARCH_PASSWORD') ? \getenv('ELASTICSEARCH_PASSWORD') : null;
    }

    /**
     * Is use SSL for connect to elasticsearch?
     *
     * @return bool
     */
    protected static function isElasticsearchSsl(): bool
    {
        return \getenv('ELASTICSEARCH_SSL') ? true : false;
    }

    /**
     * Can testing with elasticsearch?
     *
     * @return bool
     */
    protected static function canTestingWithElasticsearch(): bool
    {
        return self::getElasticsearchHost() && self::getElasticsearchPort();
    }

    /**
     * Get connection parameters to elasticsearch
     *
     * @return ElasticsearchConnectionParameters
     */
    protected static function getElasticsearchConnectionParameters(): ElasticsearchConnectionParameters
    {
        return new ElasticsearchConnectionParameters(
            self::getElasticsearchHost(),
            self::getElasticsearchPort(),
            self::getElasticsearchUser(),
            self::getElasticsearchPassword(),
            self::isElasticsearchSsl()
        );
    }

    /**
     * Get connection parameters to opensearch
     *
     * @return ElasticsearchConnectionParameters
     */
    protected static function getOpenSearchConnectionParameters(): ElasticsearchConnectionParameters
    {
        return new ElasticsearchConnectionParameters(
            self::getOpenSearchHost(),
            self::getOpenSearchPort(),
            self::getOpenSearchUser(),
            self::getOpenSearchPassword(),
            self::isOpenSearchSsl()
        );
    }

    /**
     * Create an elasticsearch client
     *
     * @return ElasticsearchClient
     */
    protected function createElasticsearchClient(): ElasticsearchClient
    {
        $connectionParameters = $this->getElasticsearchConnectionParameters();

        return ElasticsearchClientBuilder::create()
            ->setHosts([$connectionParameters->getDsn()])
            ->build();
    }

    /**
     * Get the opensearch host
     *
     * @return string|null
     */
    protected static function getOpenSearchHost(): ?string
    {
        return \getenv('OPENSEARCH_HOST') ?: null;
    }

    /**
     * Get opensearch port
     *
     * @return int|null
     */
    protected static function getOpenSearchPort(): int
    {
        return \getenv('OPENSEARCH_PORT') ? (int) \getenv('OPENSEARCH_PORT') : 9200;
    }

    /**
     * Get the user for connect to openSearch
     *
     * @return string
     */
    protected static function getOpenSearchUser(): ?string
    {
        return \getenv('OPENSEARCH_USER') ? \getenv('OPENSEARCH_USER') : null;
    }

    /**
     * Get the password for connect to openSearch
     *
     * @return string|null
     */
    protected static function getOpenSearchPassword(): ?string
    {
        return \getenv('OPENSEARCH_PASSWORD') ? \getenv('OPENSEARCH_PASSWORD') : null;
    }

    /**
     * Is use SSL for connect to opensearch?
     *
     * @return bool
     */
    protected static function isOpenSearchSsl(): bool
    {
        return \getenv('OPENSEARCH_SSL') ? true : false;
    }

    /**
     * Can testing with opensearch?
     *
     * @return bool
     */
    protected static function canTestingWithOpenSearch(): bool
    {
        return self::getOpenSearchHost() && self::getOpenSearchPort();
    }

    /**
     * Create an opensearch client
     *
     * @return OpenSearchClient
     */
    protected function createOpenSearchClient(): OpenSearchClient
    {
        $connectionParameters = $this->getOpenSearchConnectionParameters();

        return OpenSearchClientBuilder::create()
            ->setHosts([$connectionParameters->getDsn()])
            ->build();
    }

    /**
     * Mark test skipped if not configured
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     */
    protected function markTestSkippedIfNotConfigured($clientBuilder): void
    {
        if ($clientBuilder instanceof ElasticsearchClientBuilder && !$this->canTestingWithElasticsearch()) {
            self::markTestSkipped('The Elasticsearch is not configured.');
        }

        if ($clientBuilder instanceof OpenSearchClientBuilder && !$this->canTestingWithOpenSearch()) {
            self::markTestSkipped('The OpenSearch is not configured.');
        }
    }

    /**
     * Client builders provider
     *
     * @return array
     */
    public static function clientBuildersProvider(): array
    {
        return [
            [ElasticsearchClientBuilder::create(), self::getElasticsearchConnectionParameters()],
            [OpenSearchClientBuilder::create(), self::getElasticsearchConnectionParameters()],
        ];
    }
}
