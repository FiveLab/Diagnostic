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
use PHPUnit\Framework\TestCase;

class ElasticsearchConnectionParametersTest extends TestCase
{
    /**
     * @test
     *
     * @param string      $host
     * @param int         $port
     * @param string|null $username
     * @param string|null $password
     * @param bool        $ssl
     * @param string|null $expectedDsn
     *
     * @dataProvider provideConnectionParameters
     */
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

    /**
     * Provide connection parameters
     *
     * @return array
     */
    public function provideConnectionParameters(): array
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
