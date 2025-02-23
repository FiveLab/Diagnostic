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

use FiveLab\Component\Diagnostic\Check\Mongo\MongoConnectionParameters;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MongoConnectionParametersTest extends TestCase
{
    #[Test]
    #[DataProvider('provideConnectionParameters')]
    public function shouldGetDsn(string $protocol, string $host, int $port, string $username, string $password, string $db, array $options, ?string $expectedDsn = null): void
    {
        $connectionParameters = new MongoConnectionParameters(
            $protocol,
            $host,
            $port,
            $username,
            $password,
            $db,
            $options
        );

        self::assertEquals($expectedDsn, $connectionParameters->getDsn());
    }

    public static function provideConnectionParameters(): array
    {
        return [
            'with options' => [
                'mongodb',
                'some',
                27017,
                'user',
                'pass',
                'db',
                [
                    'tls' => true,
                    'w' => 'majority',
                    'wtimeOutMS' => 0,
                ],
                'mongodb://user:pass@some:27017/?tls=true&w=majority&wtimeOutMS=0',
            ],
            'without options' => [
                'mongodb',
                'foo-bar',
                27017,
                'user',
                'pass',
                'db',
                [],
                'mongodb://user:pass@foo-bar:27017',
            ],
        ];
    }
}
