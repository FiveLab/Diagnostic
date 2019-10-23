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
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchTemplateCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;

class ElasticsearchTemplateCheckTest extends AbstractElasticsearchTestCase
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

        $client->indices()->putTemplate([
            'name' => 'test-template',
            'body' => [
                'template' => 'my-test-indices-*',
                'settings' => [
                    'number_of_shards'     => 3,
                    'index.mapper.dynamic' => false,
                ],
            ],
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

        $client->indices()->deleteTemplate(['name' => 'test-template']);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckWithoutParametersAndTemplate(): void
    {
        $check = new ElasticsearchTemplateCheck($this->getConnectionParameters(), 'test-template');

        $result = $check->check();

        self::assertEquals(new Success('Success check Elasticsearch template.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckWithPatterns(): void
    {
        $check = new ElasticsearchTemplateCheck($this->getConnectionParameters(), 'test-template', ['my-test-indices-*']);

        $result = $check->check();

        self::assertEquals(new Success('Success check Elasticsearch template.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckWithSettings(): void
    {
        $check = new ElasticsearchTemplateCheck(
            $this->getConnectionParameters(),
            'test-template',
            ['my-test-indices-*'],
            [
                'index.number_of_shards' => '3',
                'index.mapper.dynamic'   => 'false',
            ]
        );

        $result = $check->check();

        self::assertEquals(new Success('Success check Elasticsearch template.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfCannotConnect(): void
    {
        $check = new ElasticsearchTemplateCheck(new ElasticsearchConnectionParameters('some', 9201), 'some');

        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to ElasticSearch: No alive nodes found in your cluster.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfTemplateNotFound(): void
    {
        $check = new ElasticsearchTemplateCheck($this->getConnectionParameters(), 'some-foo');

        $result = $check->check();

        self::assertEquals(new Failure('The template was not found in Elasticsearch.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfPatternsAreWrong(): void
    {
        $check = new ElasticsearchTemplateCheck($this->getConnectionParameters(), 'test-template', ['some-*', 'foo-*']);

        $result = $check->check();

        self::assertEquals(new Failure('Fail check index patterns.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfSettingIsMissed(): void
    {
        $check = new ElasticsearchTemplateCheck($this->getConnectionParameters(), 'test-template', [], [
            'index.number_of_replicas' => 1,
        ]);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "index.number_of_replicas" is missed.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfPartOfPathSettingIsMissed(): void
    {
        $check = new ElasticsearchTemplateCheck($this->getConnectionParameters(), 'test-template', [], [
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
        $check = new ElasticsearchTemplateCheck($this->getConnectionParameters(), 'test-template', [], [
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
        $check = new ElasticsearchTemplateCheck(
            $this->getConnectionParameters(),
            'test-template',
            ['my-test-indices-*'],
            [
                'index.number_of_shards' => '3',
                'index.mapper.dynamic'   => 'false',
            ]
        );

        $check->check();

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'host'                    => $this->getElasticsearchHost(),
            'port'                    => $this->getElasticsearchPort(),
            'ssl'                     => $this->isElasticsearchSsl() ? 'yes' : 'no',
            'template'                => 'test-template',
            'expected index patterns' => ['my-test-indices-*'],
            'actual index patterns'   => ['my-test-indices-*'],
            'expected settings'       => [
                'index.number_of_shards' => '3',
                'index.mapper.dynamic'   => 'false',
            ],
            'actual settings'         => [
                'index' => [
                    'number_of_shards' => '3',
                    'mapper'           => [
                        'dynamic' => 'false',
                    ],
                ],
            ],
        ], $parameters);
    }
}
