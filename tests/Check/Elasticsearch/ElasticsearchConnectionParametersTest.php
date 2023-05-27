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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ElasticsearchConnectionParametersTest extends TestCase
{
    #[Test]
    #[DataProvider('provideConnectionParameters')]
    public function shouldSuccessGetDsn(string $host, int $port, string $username = null, string $password = null, bool $ssl = false, string $expectedDsn = null): void
    {
        $connectionParameters = new ElasticsearchConnectionParameters(
            $host,
            $port,
            $username,
            $password,
            $ssl
        );

        self::assertEquals($expectedDsn, $connectionParameters->getDsn());
    }

    #[Test]
    #[TestWith(['http://localhost', new ElasticsearchConnectionParameters('localhost', 9200, null, null, false)])]
    #[TestWith(['https://foo:bar@domain.com:9201/bar', new ElasticsearchConnectionParameters('domain.com', 9201, 'foo', 'bar', true)])]
    public function shouldSuccessCreateFromDsn(string $dsn, ElasticsearchConnectionParameters $expected): void
    {
        $parameters = ElasticsearchConnectionParameters::fromDsn($dsn);

        self::assertEquals($expected, $parameters);
    }

    /**
     * Provide connection parameters
     *
     * @return array
     */
    public static function provideConnectionParameters(): array
    {
        return [
            'full without ssl' => [
                'some',
                9200,
                'user',
                'pass',
                false,
                'http://user:pass@some:9200',
            ],

            'full with ssl' => [
                'foo-bar',
                9201,
                'user',
                'pass',
                true,
                'https://user:pass@foo-bar:9201',
            ],

            'without user pass' => [
                'local',
                9200,
                null,
                null,
                false,
                'http://local:9200',
            ],
        ];
    }
}
