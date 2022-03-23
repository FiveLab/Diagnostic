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

namespace FiveLab\Component\Diagnostic\Tests\Check\Mongo;

use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use FiveLab\Component\Diagnostic\Check\Mongo\MongoConnectionParameters;
use JetBrains\PhpStorm\ArrayShape;
use PHPUnit\Framework\TestCase;

class MongoConnectionParametersTest extends TestCase
{
    /**
     * @test
     *
     * @param string      $host
     * @param int         $port
     * @param string      $username
     * @param string      $password
     * @param string      $db
     * @param bool        $ssl
     * @param string|null $expectedDsn
     * @return void
     *
     * @dataProvider provideConnectionParameters
     */
    public function testGetDsn(string $host, int $port, string $username, string $password, string $db, bool $ssl = false, string $expectedDsn = null): void
    {
        $connectionParameters = new MongoConnectionParameters(
            $host,
            $port,
            $username,
            $password,
            $db,
            $ssl
        );

        self::assertEquals($expectedDsn, $connectionParameters->getDsn());
    }

    /** @return array<string,array>> */
    public function provideConnectionParameters(): array
    {
        return [
            'ssl' => [
                'some',
                27017,
                'user',
                'pass',
                'db',
                false,
                'mongodb://user:pass@some:27017/db',
            ],

            'no ssl' => [
                'foo-bar',
                27017,
                'user',
                'pass',
                'db',
                true,
                'mongodb+srv://user:pass@foo-bar:27017/db',
            ],
        ];
    }
}
