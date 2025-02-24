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
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ElasticsearchIndexCheckTest extends AbstractElasticsearchTestCase
{
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

        $http = new HttpAdapter();

        $headers = [
            'content-type' => 'application/json',
            'accept'       => 'application/json',
        ];

        if ($this->canTestingWithElasticsearch()) {
            $esParameters = $this->getElasticsearchConnectionParameters();

            $request = $http->createRequest('PUT', $esParameters->getDsn().'/test-index', $headers, \json_encode($settings));
            $http->sendRequest($request);
        }

        if ($this->canTestingWithOpenSearch()) {
            $osParameters = $this->getOpenSearchConnectionParameters();

            $request = $http->createRequest('PUT', $osParameters->getDsn().'/test-index', $headers, \json_encode($settings));
            $http->sendRequest($request);
        }
    }

    protected function tearDown(): void
    {
        $http = new HttpAdapter();

        if ($this->canTestingWithElasticsearch()) {
            $esParameters = $this->getElasticsearchConnectionParameters();

            $request = $http->createRequest('DELETE', $esParameters->getDsn().'/test-index');
            $http->sendRequest($request);
        }

        if ($this->canTestingWithOpenSearch()) {
            $osParameters = $this->getOpenSearchConnectionParameters();

            $request = $http->createRequest('DELETE', $osParameters->getDsn().'/test-index');
            $http->sendRequest($request);
        }
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldSuccessCheckWithoutParameters(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchIndexCheck($connectionParameters, 'test-index', []);

        $result = $check->check();

        self::assertEquals(new Success('Success check "test-index" index.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldSuccessCheckWithSettings(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchIndexCheck(
            $connectionParameters,
            'test-index',
            [
                'index.number_of_shards' => '3',
                'index.refresh_interval' => '5s',
            ]
        );

        $result = $check->check();

        self::assertEquals(new Success('Success check "test-index" index.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldFailIfCannotConnect(string $target): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchIndexCheck(new ElasticsearchConnectionParameters('some', 9201), 'some', []);

        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to http://some:9201 with error: cURL error 6: Could not resolve host: some (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for http://some:9201/some/_settings.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldFailIfIndexNotFound(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchIndexCheck($connectionParameters, 'some-foo', []);

        $result = $check->check();

        self::assertEquals(new Failure('Fail check: no such index [some-foo]'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldFailIfSettingIsMissed(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchIndexCheck($connectionParameters, 'test-index', [
            'index.number_of_replica' => 1,
        ]);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "index.number_of_replica" is missed.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldFailIfPartOfPathSettingIsMissed(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchIndexCheck($connectionParameters, 'test-index', [
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

        $check = new ElasticsearchIndexCheck($connectionParameters, 'test-index', [
            'index.number_of_shards' => '5',
        ]);

        $result = $check->check();

        self::assertEquals(new Failure('The setting "index.number_of_shards" is different to expected.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldSuccessGetParameters(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchIndexCheck(
            $connectionParameters,
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
            'dsn'               => $connectionParameters->getDsn(true),
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
