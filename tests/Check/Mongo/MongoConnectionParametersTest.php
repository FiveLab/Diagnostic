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
     * @param bool        $ssl
     * @param string|null $expectedDsn
     * @return void
     *
     * @dataProvider provideConnectionParameters
     */
    public function testGetDsn(string $host, int $port, bool $ssl = false, string $expectedDsn = null): void
    {
        $connectionParameters = new MongoConnectionParameters(
            $host,
            $port,
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
                false,
                'mongodb://some:27017',
            ],

            'no ssl' => [
                'foo-bar',
                27017,
                true,
                'mongodb+srv://foo-bar:27017',
            ],
        ];
    }
}
