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
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchIndexCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;

class ElasticsearchIndexCheckTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $settings = [
            'settings' => [
                'number_of_shards'         => 3,
                'index.number_of_replicas' => 1,
                'index.refresh_interval'   => '5s',
            ],
            'mappings' => [
                'dynamic'    => false,
                'properties' => [
                    'login' => [
                        'type' => 'text',
                    ],
                ],
            ],
        ];

        if ($this->canTestingWithElasticsearch()) {
            $client = $this->createElasticsearchClient();
            $client->indices()->create([
                'index' => 'test-index',
                'body'  => $settings,
            ]);
        }

        if ($this->canTestingWithOpenSearch()) {
            $client = $this->createOpenSearchClient();

            $client->indices()->create([
                'index' => 'test-index',
                'body'  => $settings,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        if ($this->canTestingWithElasticsearch()) {
            $client = $this->createElasticsearchClient();
            $client->indices()->delete(['index' => 'test-index']);
        }

        if ($this->canTestingWithOpenSearch()) {
            $client = $this->createOpenSearchClient();
            $client->indices()->delete(['index' => 'test-index']);
        }
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     */
    public function shouldSuccessCheckWithoutParameters($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchIndexCheck($connectionParameters, 'test-index', [], $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Success(\sprintf('Success check %s index.', $check->getEngineName())), $result);
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     */
    public function shouldSuccessCheckWithSettings($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchIndexCheck(
            $connectionParameters,
            'test-index',
            [
                'index.number_of_shards' => '3',
                'index.refresh_interval' => '5s',
            ],
            $clientBuilder
        );

        $result = $check->check();

        self::assertEquals(new Success(\sprintf('Success check %s index.', $check->getEngineName())), $result);
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     */
    public function shouldFailIfCannotConnect($clientBuilder): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchIndexCheck(new ElasticsearchConnectionParameters('some', 9201), 'some', [], $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Failure(\sprintf('Fail connect to %s: No alive nodes found in your cluster.', $check->getEngineName())), $result);
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     */
    public function shouldFailIfIndexNotFound($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchIndexCheck($connectionParameters, 'some-foo', [], $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Failure(\sprintf('The index was not found in %s.', $check->getEngineName())), $result);
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     */
    public function shouldFailIfSettingIsMissed($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchIndexCheck($connectionParameters, 'test-index', [
            'index.number_of_replica' => 1,
        ], $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "index.number_of_replica" is missed.'), $result);
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     */
    public function shouldFailIfPartOfPathSettingIsMissed($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchIndexCheck($connectionParameters, 'test-index', [
            'some.foo.bar' => 1,
        ], $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "some" is missed.'), $result);
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     */
    public function shouldFailIfSettingIsDifferent($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchIndexCheck($connectionParameters, 'test-index', [
            'index.number_of_shards' => '5',
        ], $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "index.number_of_shards" is different to expected.'), $result);
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     */
    public function shouldSuccessGetParameters($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchIndexCheck(
            $connectionParameters,
            'test-index',
            [
                'index.number_of_shards'   => '3',
                'index.refresh_interval'   => '5s',
                'index.number_of_replicas' => '1',
            ],
            $clientBuilder
        );

        $check->check();

        $parameters = $check->getExtraParameters();

        // unset extra params
        unset($parameters['actual settings']['index']['uuid']);
        unset($parameters['actual settings']['index']['version']);
        unset($parameters['actual settings']['index']['creation_date']);

        self::assertEquals([
            'host'              => $this->getElasticsearchHost(),
            'port'              => $this->getElasticsearchPort(),
            'ssl'               => $this->isElasticsearchSsl() ? 'yes' : 'no',
            'index'             => 'test-index',
            'expected settings' => [
                'index.number_of_shards'   => '3',
                'index.refresh_interval'   => '5s',
                'index.number_of_replicas' => '1',
            ],
            'actual settings'   => [
                'index' => [
                    'number_of_shards'   => '3',
                    'provided_name'      => 'test-index',
                    'refresh_interval'   => '5s',
                    'number_of_replicas' => '1',
                ],
            ],
        ], $parameters);
    }
}
