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
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionCheck;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;

class ElasticsearchConnectionCheckTest extends AbstractElasticsearchTestCase
{
    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     */
    public function shouldSuccessCheck($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchConnectionCheck($clientBuilder, $connectionParameters);

        $result = $check->check();

        self::assertEquals(new Success(\sprintf('Success connect to %s and send ping request.', $check->getEngineName())), $result);
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     */
    public function shouldFailCheckIfHostIsInvalid($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $connectionParameters = new ElasticsearchConnectionParameters(
            $connectionParameters->getHost().'_some',
            $connectionParameters->getPort(),
            $connectionParameters->getUsername(),
            $connectionParameters->getPassword(),
            false
        );

        $check = new ElasticsearchConnectionCheck($clientBuilder, $connectionParameters);

        $result = $check->check();

        self::assertEquals(new Failure(\sprintf('Fail connect to %s: No alive nodes found in your cluster.', $check->getEngineName())), $result);
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     */
    public function shouldSuccessGetExtraParametersWithoutUserAndPass($clientBuilder): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchConnectionCheck($clientBuilder, new ElasticsearchConnectionParameters('some', 9201));

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'host' => 'some',
            'port' => 9201,
            'ssl'  => 'no',
        ], $parameters);
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     */
    public function shouldSuccessGetExtraParametersWithUserAndPass($clientBuilder): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $connectionParameters = new ElasticsearchConnectionParameters(
            'foo',
            9202,
            'some',
            'bar-foo',
            true
        );

        $check = new ElasticsearchConnectionCheck($clientBuilder, $connectionParameters);

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'host' => 'foo',
            'port' => 9202,
            'ssl'  => 'yes',
            'user' => 'some',
            'pass' => '***',
        ], $parameters);
    }
}
