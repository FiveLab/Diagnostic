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

namespace FiveLab\Component\Diagnostic\Tests\Check\ClickHouse;

use FiveLab\Component\Diagnostic\Check\ClickHouse\ClickHouseConnectCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractClickHouseTestCase;
use FiveLab\Component\Diagnostic\Util\Dsn;
use PHPUnit\Framework\Attributes\Test;

class ClickHouseConnectCheckTest extends AbstractClickHouseTestCase
{
    protected function setUp(): void
    {
        if (!$this->canTestWithClickHouse()) {
            self::markTestSkipped('click house not configured');
        }
    }

    #[Test]
    public function shouldSuccessCheck(): void
    {
        $check = new ClickHouseConnectCheck($this->getClickHouseDsn());
        $result = $check->check();

        self::assertEquals(new Success('Success connect to ClickHouse.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtraParameters(): void
    {
        $check = new ClickHouseConnectCheck($this->getClickHouseDsn());
        $extraParams = $check->getExtraParameters();

        self::assertEquals([
            'dsn' => $this->getClickHouseDsn()->getDsn(maskedPassword: true),
        ], $extraParams);
    }

    #[Test]
    public function shouldFailWithWrongHost(): void
    {
        $dsn = new Dsn(
            'http',
            \uniqid(),
            '/'.$this->getClickHouseDb(),
            $this->getClickHousePort(),
            $this->getClickHouseUser(),
            $this->getClickHousePassword()
        );

        $check = new ClickHouseConnectCheck($dsn);
        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Fail connect to', $result->message);
        self::assertStringContainsString('cURL error 6: Could not resolve host', $result->message);
    }
}
