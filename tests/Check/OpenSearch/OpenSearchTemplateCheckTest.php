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

namespace FiveLab\Component\Diagnostic\Tests\Check\OpenSearch;

use FiveLab\Component\Diagnostic\Check\OpenSearch\OpenSearchConnectionParameters;
use FiveLab\Component\Diagnostic\Check\OpenSearch\OpenSearchTemplateCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractOpenSearchTestCase;

class OpenSearchTemplateCheckTest extends AbstractOpenSearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithOpenSearch()) {
            self::markTestSkipped('The OpenSearch is not configured.');
        }

        $client = $this->createClient();

        $client->indices()->putTemplate([
            'name' => 'test-template',
            'body' => [
                'template' => 'my-test-indices-*',
                'settings' => [
                    'number_of_shards'       => 3,
                    'index.refresh_interval' => '10s',
                ],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        if (!$this->canTestingWithOpenSearch()) {
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
        $check = new OpenSearchTemplateCheck($this->getConnectionParameters(), 'test-template');

        $result = $check->check();

        self::assertEquals(new Success('Success check OpenSearch template.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckWithPatterns(): void
    {
        $check = new OpenSearchTemplateCheck($this->getConnectionParameters(), 'test-template', ['my-test-indices-*']);

        $result = $check->check();

        self::assertEquals(new Success('Success check OpenSearch template.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckWithSettings(): void
    {
        $check = new OpenSearchTemplateCheck(
            $this->getConnectionParameters(),
            'test-template',
            ['my-test-indices-*'],
            [
                'index.number_of_shards' => '3',
                'index.refresh_interval' => '10s',
            ]
        );

        $result = $check->check();

        self::assertEquals(new Success('Success check OpenSearch template.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfCannotConnect(): void
    {
        $check = new OpenSearchTemplateCheck(new OpenSearchConnectionParameters('some', 9201), 'some');

        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to OpenSearch: No alive nodes found in your cluster.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfTemplateNotFound(): void
    {
        $check = new OpenSearchTemplateCheck($this->getConnectionParameters(), 'some-foo');

        $result = $check->check();

        self::assertEquals(new Failure('The template was not found in OpenSearch.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfPatternsAreWrong(): void
    {
        $check = new OpenSearchTemplateCheck($this->getConnectionParameters(), 'test-template', ['some-*', 'foo-*']);

        $result = $check->check();

        self::assertEquals(new Failure('Fail check index patterns.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfSettingIsMissed(): void
    {
        $check = new OpenSearchTemplateCheck($this->getConnectionParameters(), 'test-template', [], [
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
        $check = new OpenSearchTemplateCheck($this->getConnectionParameters(), 'test-template', [], [
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
        $check = new OpenSearchTemplateCheck($this->getConnectionParameters(), 'test-template', [], [
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
        $check = new OpenSearchTemplateCheck(
            $this->getConnectionParameters(),
            'test-template',
            ['my-test-indices-*'],
            [
                'index.number_of_shards' => '3',
                'index.refresh_interval' => '10s',
            ]
        );

        $check->check();

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'host'                    => $this->getOpenSearchHost(),
            'port'                    => $this->getOpenSearchPort(),
            'ssl'                     => $this->isOpenSearchSsl() ? 'yes' : 'no',
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
