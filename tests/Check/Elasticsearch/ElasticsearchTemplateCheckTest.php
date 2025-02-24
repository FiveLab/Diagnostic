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
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ElasticsearchTemplateCheckTest extends AbstractElasticsearchTestCase
{
    protected function setUp(): void
    {
        $template = [
            'index_patterns' => ['my-test-indices-*'],
            'settings' => [
                'number_of_shards'       => 3,
                'index.refresh_interval' => '10s',
            ],
        ];

        $http = new HttpAdapter();

        $headers = [
            'content-type' => 'application/json',
            'accept'       => 'application/json',
        ];

        if ($this->canTestingWithElasticsearch()) {
            $esParameters = $this->getElasticsearchConnectionParameters();

            $request = $http->createRequest('PUT', $esParameters->getDsn().'/_template/test-template', $headers, \json_encode($template));
            $http->sendRequest($request);
        }

        if ($this->canTestingWithOpenSearch()) {
            $osParameters = $this->getOpenSearchConnectionParameters();

            $request = $http->createRequest('PUT', $osParameters->getDsn().'/_template/test-template', $headers, \json_encode($template));
            $http->sendRequest($request);
        }
    }

    protected function tearDown(): void
    {
        $http = new HttpAdapter();

        if ($this->canTestingWithElasticsearch()) {
            $esParameters = $this->getElasticsearchConnectionParameters();

            $request = $http->createRequest('DELETE', $esParameters->getDsn().'/_index_template/test-template');
            $http->sendRequest($request);
        }

        if ($this->canTestingWithOpenSearch()) {
            $osParameters = $this->getOpenSearchConnectionParameters();

            $request = $http->createRequest('DELETE', $osParameters->getDsn().'/_index_template/test-template');
            $http->sendRequest($request);
        }
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldSuccessCheckWithoutParametersAndTemplate(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'test-template', [], []);

        $result = $check->check();

        self::assertEquals(new Success('Success check "test-template" template.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldSuccessCheckWithPatterns(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'test-template', ['my-test-indices-*'], []);

        $result = $check->check();

        self::assertEquals(new Success('Success check "test-template" template.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldSuccessCheckWithSettings(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchTemplateCheck(
            $connectionParameters,
            'test-template',
            ['my-test-indices-*'],
            [
                'index.number_of_shards' => '3',
                'index.refresh_interval' => '10s',
            ]
        );

        $result = $check->check();

        self::assertEquals(new Success('Success check "test-template" template.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldFailIfCannotConnect(string $target): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchTemplateCheck(new ElasticsearchConnectionParameters('some', 9201), 'some', [], []);

        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to http://some:9201 with error: cURL error 6: Could not resolve host: some (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for http://some:9201/_template/some.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldFailIfTemplateNotFound(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'some-foo', [], []);

        $result = $check->check();

        self::assertEquals(new Failure('The index template "some-foo" was not found.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldFailIfPatternsAreWrong(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'test-template', ['some-*', 'foo-*'], []);

        $result = $check->check();

        self::assertEquals(new Failure('Fail check index patterns.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldFailIfSettingIsMissed(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'test-template', [], [
            'index.number_of_replicas' => 1,
        ]);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "index.number_of_replicas" is missed.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldFailIfPartOfPathSettingIsMissed(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'test-template', [], [
            'some.foo.bar' => 1,
        ]);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "some" is missed.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldFailIfSettingIsDifferent(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchTemplateCheck($connectionParameters, 'test-template', [], [
            'index.number_of_shards' => '5',
        ]);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "index.number_of_shards" is different to expected.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldSuccessGetParameters(string $taget, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($taget);

        $check = new ElasticsearchTemplateCheck(
            $connectionParameters,
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
            'dsn'                     => $connectionParameters->getDsn(true),
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
