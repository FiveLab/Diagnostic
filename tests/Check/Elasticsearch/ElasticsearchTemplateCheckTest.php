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
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchTemplateCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;

class ElasticsearchTemplateCheckTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $template = [
            'name' => 'test-template',
            'body' => [
                'template' => 'my-test-indices-*',
                'settings' => [
                    'number_of_shards'       => 3,
                    'index.refresh_interval' => '10s',
                ],
            ],
        ];

        if ($this->canTestingWithElasticsearch()) {
            $client = $this->createElasticsearchClient();
            $client->indices()->putTemplate($template);
        }

        if ($this->canTestingWithOpenSearch()) {
            $client = $this->createOpenSearchClient();
            $client->indices()->putTemplate($template);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        if ($this->canTestingWithElasticsearch()) {
            $client = $this->createElasticsearchClient();
            $client->indices()->deleteTemplate(['name' => 'test-template']);
        }

        if ($this->canTestingWithOpenSearch()) {
            $client = $this->createOpenSearchClient();
            $client->indices()->deleteTemplate(['name' => 'test-template']);
        }
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     */
    public function shouldSuccessCheckWithoutParametersAndTemplate($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'test-template', [], [], $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Success('Success check Elasticsearch template.'), $result);
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     */
    public function shouldSuccessCheckWithPatterns($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'test-template', ['my-test-indices-*'], [], $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Success('Success check Elasticsearch template.'), $result);
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

        $check = new ElasticsearchTemplateCheck(
            $connectionParameters,
            'test-template',
            ['my-test-indices-*'],
            [
                'index.number_of_shards' => '3',
                'index.refresh_interval' => '10s',
            ],
            $clientBuilder
        );

        $result = $check->check();

        self::assertEquals(new Success('Success check Elasticsearch template.'), $result);
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

        $check = new ElasticsearchTemplateCheck(new ElasticsearchConnectionParameters('some', 9201), 'some', [], [], $clientBuilder);

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
    public function shouldFailIfTemplateNotFound($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'some-foo', [], [], $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Failure(\sprintf('The template was not found in %s.', $check->getEngineName())), $result);
    }

    /**
     * @test
     * @dataProvider clientBuildersProvider
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     */
    public function shouldFailIfPatternsAreWrong($clientBuilder, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($clientBuilder);

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'test-template', ['some-*', 'foo-*'], [], $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Failure('Fail check index patterns.'), $result);
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

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'test-template', [], [
            'index.number_of_replicas' => 1,
        ], $clientBuilder);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "index.number_of_replicas" is missed.'), $result);
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

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'test-template', [], [
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

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'test-template', [], [
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

        $check = new ElasticsearchTemplateCheck(
            $connectionParameters,
            'test-template',
            ['my-test-indices-*'],
            [
                'index.number_of_shards' => '3',
                'index.refresh_interval' => '10s',
            ],
            $clientBuilder
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
                'index.refresh_interval' => '10s',
            ],
            'actual settings'         => [
                'index' => [
                    'number_of_shards' => '3',
                    'refresh_interval' => '10s',
                ],
            ],
        ], $parameters);
    }
}
