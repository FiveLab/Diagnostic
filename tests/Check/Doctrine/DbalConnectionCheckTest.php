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

namespace FiveLab\Component\Diagnostic\Tests\Check\Doctrine;

use FiveLab\Component\Diagnostic\Check\Doctrine\DbalConnectionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use PHPUnit\Framework\Attributes\Test;

class DbalConnectionCheckTest extends AbstractDoctrineCheckTestCase
{
    #[Test]
    public function shouldSuccessCheck(): void
    {
        $check = new DbalConnectionCheck($this->makeDbalConnection());

        $result = $check->check();

        self::assertEquals(new Success('Success connect to database.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtraParameters(): void
    {
        $check = new DbalConnectionCheck($this->makeDbalConnection());

        self::assertEquals([
            'host'   => $this->getDatabaseHost(),
            'port'   => $this->getDatabasePort(),
            'user'   => $this->getDatabaseUser(),
            'pass'   => '***',
            'dbname' => $this->getDatabaseName(),
        ], $check->getExtraParameters());
    }

    #[Test]
    public function shouldFailIfCredentialsIsWrong(): void
    {
        $connection = $this->makeDbalConnection([
            'password' => \uniqid(),
        ]);

        $check = new DbalConnectionCheck($connection);

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringContainsString(
            'SQLSTATE[HY000] [1045]',
            $result->getMessage()
        );
    }

    #[Test]
    public function shouldFailIfHostIsInvalid(): void
    {
        $connection = $this->makeDbalConnection([
            'host' => \uniqid(),
        ]);

        $check = new DbalConnectionCheck($connection);

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringContainsString(
            'SQLSTATE[HY000] [2002]',
            $result->getMessage()
        );
    }
}
