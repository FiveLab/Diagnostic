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
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchIndexCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;

class ElasticsearchIndexCheckTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithElasticsearch()) {
            self::markTestSkipped('The ElasticSearch is not configured.');
        }

        $client = $this->createClient();

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

        $client->indices()->create([
            'index' => 'test-index',
            'body'  => $settings,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        if (!$this->canTestingWithElasticsearch()) {
            return;
        }

        $client = $this->createClient();

        $client->indices()->delete(['index' => 'test-index']);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckWithoutParameters(): void
    {
        $check = new ElasticsearchIndexCheck($this->getConnectionParameters(), 'test-index');

        $result = $check->check();

        self::assertEquals(new Success('Success check Elasticsearch index.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckWithSettings(): void
    {
        $check = new ElasticsearchIndexCheck(
            $this->getConnectionParameters(),
            'test-index',
            [
                'index.number_of_shards' => '3',
                'index.refresh_interval' => '5s',
            ]
        );

        $result = $check->check();

        self::assertEquals(new Success('Success check Elasticsearch index.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfCannotConnect(): void
    {
        $check = new ElasticsearchIndexCheck(new ElasticsearchConnectionParameters('some', 9201), 'some');

        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to ElasticSearch: No alive nodes found in your cluster.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfIndexNotFound(): void
    {
        $check = new ElasticsearchIndexCheck($this->getConnectionParameters(), 'some-foo');

        $result = $check->check();

        self::assertEquals(new Failure('The index was not found in Elasticsearch.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfSettingIsMissed(): void
    {
        $check = new ElasticsearchIndexCheck($this->getConnectionParameters(), 'test-index', [
            'index.number_of_replica' => 1,
        ]);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "index.number_of_replica" is missed.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfPartOfPathSettingIsMissed(): void
    {
        $check = new ElasticsearchIndexCheck($this->getConnectionParameters(), 'test-index', [
            'some.foo.bar' => 1,
        ]);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "some" is missed.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfSettingIsDifferent(): void
    {
        $check = new ElasticsearchIndexCheck($this->getConnectionParameters(), 'test-index', [
            'index.number_of_shards' => '5',
        ]);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "index.number_of_shards" is different to expected.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetParameters(): void
    {
        $check = new ElasticsearchIndexCheck(
            $this->getConnectionParameters(),
            'test-index',
            [
                'index.number_of_shards'   => '3',
                'index.refresh_interval'   => '5s',
                'index.number_of_replicas' => '1',
            ]
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
