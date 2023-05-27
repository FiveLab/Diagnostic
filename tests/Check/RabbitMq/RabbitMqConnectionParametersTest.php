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

namespace FiveLab\Component\Diagnostic\Tests\Check\RabbitMq;

use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RabbitMqConnectionParametersTest extends TestCase
{
    #[Test]
    #[DataProvider('provideDsns')]
    public function shouldSuccessCreateFromDsn(string $dsn, RabbitMqConnectionParameters $expected): void
    {
        $parameters = RabbitMqConnectionParameters::fromDsn($dsn);

        self::assertEquals($expected, $parameters);
    }

    /**
     * Provide data for test make via DSN.
     *
     * @return array
     */
    public static function provideDsns(): array
    {
        return [
            ['localhost:5672', new RabbitMqConnectionParameters('localhost', 5672, 'guest', 'guest', '/', false)],
            ['https://foo:bar@domain.net:5673/%2fsome', new RabbitMqConnectionParameters('domain.net', 5673, 'foo', 'bar', '/some', true)],
        ];
    }
}
