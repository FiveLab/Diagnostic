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

use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchVersionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;

class ElasticsearchVersionCheckTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithElasticsearch()) {
            self::markTestSkipped('The ElasticSearch is not configured.');
        }
    }

    /**
     * @test
     */
    public function shouldSuccessCheckVersions(): void
    {
        $check = new ElasticsearchVersionCheck($this->getConnectionParameters(), '~6.8.0', '~7.0');

        $result = $check->check();

        self::assertEquals(new Success('Success check Elasticsearch version.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckForElasticsearchVersion(): void
    {
        $check = new ElasticsearchVersionCheck($this->getConnectionParameters(), '~6.7.0');

        $result = $check->check();

        self::assertEquals(new Failure('Fail check Elasticsearch version.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckLuceneVersion(): void
    {
        $check = new ElasticsearchVersionCheck($this->getConnectionParameters(), null, '~6.0.0');

        $result = $check->check();

        self::assertEquals(new Failure('Fail check Lucene version.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfCannotConnect(): void
    {
        $check = new ElasticsearchVersionCheck(new ElasticsearchConnectionParameters('some', 9201));

        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to ElasticSearch: No alive nodes found in your cluster.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetParameters(): void
    {
        $check = new ElasticsearchVersionCheck($this->getConnectionParameters(), '~6.8.0', '~7.0');

        $check->check();
        $parameters = $check->getExtraParameters();

        self::assertEquals($this->getElasticsearchHost(), $parameters['host']);
        self::assertEquals($this->getElasticsearchPort(), $parameters['port']);
        self::assertEquals($this->isElasticsearchSsl() ? 'yes' : 'no', $parameters['ssl']);

        self::assertArrayHasKey('actual version', $parameters);
        self::assertArrayHasKey('expected version', $parameters);
        self::assertArrayHasKey('actual lucene version', $parameters);
        self::assertArrayHasKey('expected lucene version', $parameters);

        self::assertNotEmpty($parameters['actual version']);
        self::assertEquals('~6.8.0', $parameters['expected version']);
        self::assertNotEmpty($parameters['actual lucene version']);
        self::assertEquals('~7.0', $parameters['expected lucene version']);
    }
}
