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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ElasticsearchVersionCheckTest extends AbstractElasticsearchTestCase
{
    #[Test]
    #[DataProvider('successCheckVersionsProvider')]
    public function shouldSuccessCheckVersions(string $target, ElasticsearchConnectionParameters $connectionParameters, string $version, string $luceneVersion): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchVersionCheck($connectionParameters, $version, $luceneVersion, null);

        $result = $check->check();

        self::assertEquals(new Success('Success check Elasticsearch/Opensearch version.'), $result);
    }

    #[Test]
    #[DataProvider('failCheckElasticsearchVersionsProvider')]
    public function shouldFailCheckForElasticsearchVersion(string $target, ElasticsearchConnectionParameters $connectionParameters, string $version): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchVersionCheck($connectionParameters, $version, null, null);

        $result = $check->check();

        self::assertEquals(new Failure('Fail check Elasticsearch/Opensearch version.'), $result);
    }

    #[Test]
    #[DataProvider('failCheckLuceneVersionsProvider')]
    public function shouldFailCheckLuceneVersion(string $target, ElasticsearchConnectionParameters $connectionParameters, string $version): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchVersionCheck($connectionParameters, null, $version, null);

        $result = $check->check();

        self::assertEquals(new Failure('Fail check Lucene version.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldFailIfCannotConnect(string $target): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchVersionCheck(new ElasticsearchConnectionParameters('some', 9201), null, null, null);

        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to http://some:9201 with error: cURL error 6: Could not resolve host: some (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for http://some:9201/.'), $result);
    }

    #[Test]
    #[DataProvider('successGetParametersProvider')]
    public function shouldSuccessGetParameters(string $target, ElasticsearchConnectionParameters $connectionParameters, string $version, string $luceneVersion): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchVersionCheck($connectionParameters, $version, $luceneVersion, null);

        $check->check();
        $parameters = $check->getExtraParameters();

        self::assertEquals($connectionParameters->getDsn(true), $parameters['dsn']);

        self::assertArrayHasKey('actual version', $parameters);
        self::assertArrayHasKey('expected version', $parameters);
        self::assertArrayHasKey('actual lucene version', $parameters);
        self::assertArrayHasKey('expected lucene version', $parameters);

        self::assertNotEmpty($parameters['actual version']);
        self::assertEquals($version, $parameters['expected version']);
        self::assertNotEmpty($parameters['actual lucene version']);
        self::assertEquals($luceneVersion, $parameters['expected lucene version']);
    }

    public static function successCheckVersionsProvider(): array
    {
        return [
            ['Elasticsearch', self::getElasticsearchConnectionParameters(), '~7.12.0', '~8.0'],
            ['Opensearch', self::getOpenSearchConnectionParameters(), '~2.4.0', '~9.0'],
        ];
    }

    public static function failCheckElasticsearchVersionsProvider(): array
    {
        return [
            ['Elasticsearch', self::getElasticsearchConnectionParameters(), '~6.7'],
            ['Opensearch', self::getOpenSearchConnectionParameters(), '~1.4.0'],
        ];
    }

    public static function failCheckLuceneVersionsProvider(): array
    {
        return [
            ['Elasticsearch', self::getElasticsearchConnectionParameters(), '~6.0'],
            ['Opensearch', self::getOpenSearchConnectionParameters(), '~8.0'],
        ];
    }

    public static function failIfCannotConnectProvider(): array
    {
        return [
            ['Elasticsearch'],
            ['Opensearch'],
        ];
    }

    public static function successGetParametersProvider(): array
    {
        return [
            ['Elasticsearch', self::getElasticsearchConnectionParameters(), '~6.8.0', '~7.0'],
            ['Opensearch', self::getOpenSearchConnectionParameters(), '~2.4.0', '~9.0'],
        ];
    }
}
