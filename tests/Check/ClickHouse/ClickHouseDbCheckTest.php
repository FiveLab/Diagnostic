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

use FiveLab\Component\Diagnostic\Check\ClickHouse\ClickHouseDbCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractClickHouseTestCase;
use FiveLab\Component\Diagnostic\Util\Dsn;
use PHPUnit\Framework\Attributes\Test;

class ClickHouseDbCheckTest extends AbstractClickHouseTestCase
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
        $check = new ClickHouseDbCheck($this->getClickHouseDsn());
        $result = $check->check();

        self::assertEquals(new Success('Successfully checked existence of database in ClickHouse.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtraParams(): void
    {
        $check = new ClickHouseDbCheck($this->getClickHouseDsn());
        $extraParams = $check->getExtraParameters();

        self::assertEquals([
            'dsn' => $this->getClickHouseDsn()->getDsn(path: true, maskedPassword: true),
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

        $check = new ClickHouseDbCheck($dsn);
        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Fail connect to', $result->message);
    }

    #[Test]
    public function shouldFailWithWrongPassword(): void
    {
        $dsn = new Dsn(
            'http',
            $this->getClickHouseHost(),
            '/'.$this->getClickHouseDb(),
            $this->getClickHousePort(),
            $this->getClickHouseUser(),
            'bla-bla'
        );

        $check = new ClickHouseDbCheck($dsn);
        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Receive wrong status code "403", expected "200".', $result->message);
    }
}
