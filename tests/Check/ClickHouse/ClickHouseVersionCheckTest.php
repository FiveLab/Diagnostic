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
use FiveLab\Component\Diagnostic\Check\ClickHouse\ClickHouseVersionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractClickHouseTestCase;
use FiveLab\Component\Diagnostic\Util\Dsn;
use PHPUnit\Framework\Attributes\Test;

class ClickHouseVersionCheckTest extends AbstractClickHouseTestCase
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
        $check = new ClickHouseVersionCheck($this->getClickHouseDsn(), '~25.9.0');
        $result = $check->check();

        self::assertEquals(new Success('Success check ClickHouse version.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtraParams(): void
    {
        $check = new ClickHouseVersionCheck($this->getClickHouseDsn(), '~25.9.0');
        $check->check();

        $params = $check->getExtraParameters();

        self::assertEquals([
            'dsn'              => $this->getClickHouseDsn()->getDsn(path: true, maskedPassword: true),
            'actual version'   => $this->sendQuery('GET', 'SELECT version()'),
            'expected version' => '~25.9.0',
        ], $params);
    }

    #[Test]
    public function shouldFailWithWrongVersion(): void
    {
        $check = new ClickHouseVersionCheck($this->getClickHouseDsn(), '~24.0.0');
        $result = $check->check();

        self::assertEquals(new Failure('Fail check ClickHouse version.'), $result);
    }
}
