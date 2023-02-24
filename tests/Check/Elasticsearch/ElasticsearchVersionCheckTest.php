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

use Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchVersionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;

class ElasticsearchVersionCheckTest extends AbstractElasticsearchTestCase
{
    /**
     * @test
     *
     * @dataProvider successCheckVersionsProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     * @param string                                             $version
     * @param string                                             $luceneVersion
     */
    public function shouldSuccessCheckVersions($clientBuilder, ElasticsearchConnectionParameters $connectionParameters, string $version, string $luceneVersion): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchVersionCheck($connectionParameters, $version, $luceneVersion, null, $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Success(\sprintf('Success check %s version.', $check->getEngineName())), $result);
    }

    /**
     * @test
     *
     * @dataProvider failCheckElasticsearchVersionsProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     * @param string                                             $version
     */
    public function shouldFailCheckForElasticsearchVersion($clientBuilder, ElasticsearchConnectionParameters $connectionParameters, string $version): void
    {
        $check = new ElasticsearchVersionCheck($connectionParameters, $version, null, null, $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Failure(\sprintf('Fail check %s version.', $check->getEngineName())), $result);
    }

    /**
     * @test
     *
     * @dataProvider failCheckLuceneVersionsProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     * @param string                                             $version
     */
    public function shouldFailCheckLuceneVersion($clientBuilder, ElasticsearchConnectionParameters $connectionParameters, string $version): void
    {
        $check = new ElasticsearchVersionCheck($connectionParameters, null, $version, null, $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Failure('Fail check Lucene version.'), $result);
    }

    /**
     * @test
     *
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     */
    public function shouldFailIfCannotConnect($clientBuilder): void
    {
        $check = new ElasticsearchVersionCheck(new ElasticsearchConnectionParameters('some', 9201), null, null, null, $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Failure(\sprintf('Fail connect to %s: No alive nodes found in your cluster.', $check->getEngineName())), $result);
    }

    /**
     * @test
     *
     * @dataProvider successGetParametersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     * @param string                                             $version
     * @param string                                             $luceneVersion
     */
    public function shouldSuccessGetParameters($clientBuilder, ElasticsearchConnectionParameters $connectionParameters, string $version, string $luceneVersion): void
    {
        $check = new ElasticsearchVersionCheck($connectionParameters, $version, $luceneVersion, null, $clientBuilder);

        $check->check();
        $parameters = $check->getExtraParameters();

        self::assertEquals($connectionParameters->getHost(), $parameters['host']);
        self::assertEquals($connectionParameters->getPort(), $parameters['port']);
        self::assertEquals($connectionParameters->isSsl() ? 'yes' : 'no', $parameters['ssl']);

        self::assertArrayHasKey('actual version', $parameters);
        self::assertArrayHasKey('expected version', $parameters);
        self::assertArrayHasKey('actual lucene version', $parameters);
        self::assertArrayHasKey('expected lucene version', $parameters);

        self::assertNotEmpty($parameters['actual version']);
        self::assertEquals($version, $parameters['expected version']);
        self::assertNotEmpty($parameters['actual lucene version']);
        self::assertEquals($luceneVersion, $parameters['expected lucene version']);
    }

    /**
     * Success check versions provider
     *
     * @return array
     */
    public function successCheckVersionsProvider(): array
    {
        return [
            [ElasticsearchClientBuilder::create(), $this->getElasticsearchConnectionParameters(), '~7.12.0', '~8.0'],
            [OpenSearchClientBuilder::create(), $this->getOpenSearchConnectionParameters(), '~2.4.0', '~9.0'],
        ];
    }

    /**
     * Fail check elasticsearch versions provider
     *
     * @return array
     */
    public function failCheckElasticsearchVersionsProvider(): array
    {
        return [
            [ElasticsearchClientBuilder::create(), $this->getElasticsearchConnectionParameters(), '~6.7'],
            [OpenSearchClientBuilder::create(), $this->getOpenSearchConnectionParameters(), '~1.4.0'],
        ];
    }

    /**
     * Fail check lucene versions provider
     *
     * @return array
     */
    public function failCheckLuceneVersionsProvider(): array
    {
        return [
            [ElasticsearchClientBuilder::create(), $this->getElasticsearchConnectionParameters(), '~6.0'],
            [OpenSearchClientBuilder::create(), $this->getOpenSearchConnectionParameters(), '~8.0'],
        ];
    }

    /**
     * Fail if cannot connect provider
     *
     * @return array
     */
    public function failIfCannotConnectProvider(): array
    {
        return [
            [ElasticsearchClientBuilder::create()],
            [OpenSearchClientBuilder::create()],
        ];
    }

    /**
     * Success get parameters provider
     *
     * @return array
     */
    public function successGetParametersProvider(): array
    {
        return [
            [ElasticsearchClientBuilder::create(), $this->getElasticsearchConnectionParameters(), '~6.8.0', '~7.0'],
            [OpenSearchClientBuilder::create(), $this->getOpenSearchConnectionParameters(), '~2.4.0', '~9.0'],
        ];
    }
}
