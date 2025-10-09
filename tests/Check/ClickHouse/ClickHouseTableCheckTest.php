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

use FiveLab\Component\Diagnostic\Check\ClickHouse\ClickHouseTableCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractClickHouseTestCase;
use PHPUnit\Framework\Attributes\Test;

class ClickHouseTableCheckTest extends AbstractClickHouseTestCase
{
    protected function setUp(): void
    {
        if (!$this->canTestWithClickHouse()) {
            self::markTestSkipped('click house not configured');
        }

        $this->sendQuery('POST', 'CREATE TABLE test_01 (id UInt32) ENGINE = MergeTree ORDER BY id');
    }

    protected function tearDown(): void
    {
        $this->dropAllTables();
    }

    #[Test]
    public function shouldSuccessCheck(): void
    {
        $check = new ClickHouseTableCheck($this->getClickHouseDsn(), 'test_01');
        $result = $check->check();

        self::assertEquals(new Success('Successfully checked existence of table in ClickHouse.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtraParams(): void
    {
        $check = new ClickHouseTableCheck($this->getClickHouseDsn(), 'test_01');
        $params = $check->getExtraParameters();

        self::assertEquals([
            'dsn'   => $this->getClickHouseDsn()->getDsn(path: true, maskedPassword: true),
            'table' => 'test_01',
        ], $params);
    }

    #[Test]
    public function shouldFailIfTableNotFound(): void
    {
        $check = new ClickHouseTableCheck($this->getClickHouseDsn(), 'test_02');
        $result = $check->check();

        self::assertEquals(new Failure('Table does not exist in ClickHouse.'), $result);
    }
}
