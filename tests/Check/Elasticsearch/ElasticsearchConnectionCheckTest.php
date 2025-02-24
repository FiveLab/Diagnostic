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

use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionCheck;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ElasticsearchConnectionCheckTest extends AbstractElasticsearchTestCase
{
    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldSuccessCheck(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchConnectionCheck($connectionParameters);

        $result = $check->check();

        self::assertEquals(new Success('Success connect to Elasticsearch/Opensearch.'), $result);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldFailCheckIfHostIsInvalid(string $target, ElasticsearchConnectionParameters $connectionParameters): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $connectionParameters = new ElasticsearchConnectionParameters(
            $connectionParameters->host.'_some',
            $connectionParameters->port,
            $connectionParameters->username,
            $connectionParameters->password,
            false
        );

        $check = new ElasticsearchConnectionCheck($connectionParameters);

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringContainsString('with error: cURL error 6: Could not resolve host:', $result->message);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldSuccessGetExtraParametersWithoutUserAndPass(string $target): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $check = new ElasticsearchConnectionCheck(new ElasticsearchConnectionParameters('some', 9201));

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'dsn' => 'http://some:9201',
        ], $parameters);
    }

    #[Test]
    #[DataProvider('provideTargets')]
    public function shouldSuccessGetExtraParametersWithUserAndPass(string $target): void
    {
        $this->markTestSkippedIfNotConfigured($target);

        $connectionParameters = new ElasticsearchConnectionParameters(
            'foo',
            9202,
            'some',
            'bar-foo',
            true
        );

        $check = new ElasticsearchConnectionCheck($connectionParameters);

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'dsn' => 'https://some:***@foo:9202',
        ], $parameters);
    }
}
