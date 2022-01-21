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

namespace FiveLab\Component\Diagnostic\Tests\Check\Pdo;

use FiveLab\Component\Diagnostic\Check\Pdo\PdoConnectionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractDatabaseTestCase;

class PdoConnectionCheckTest extends AbstractDatabaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithDatabase()) {
            self::markTestSkipped('The database not configured.');
        }
    }

    /**
     * @test
     */
    public function shouldSuccessCheck(): void
    {
        $check = new PdoConnectionCheck(
            'mysql',
            $this->getDatabaseHost(),
            $this->getDatabasePort(),
            $this->getDatabaseName(),
            $this->getDatabaseUser(),
            $this->getDatabasePassword()
        );

        $result = $check->check();

        self::assertEquals(new Success('Success connect to database.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckIfDriverNotAvailable(): void
    {
        $check = new PdoConnectionCheck(
            'mysql-some',
            $this->getDatabaseHost(),
            $this->getDatabasePort(),
            $this->getDatabaseName(),
            $this->getDatabaseUser(),
            $this->getDatabasePassword()
        );

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Can\'t check connect to database via PDO by driver "mysql-some". The driver "mysql-some" is not supported. Available drivers are', $result->getMessage());
    }

    /**
     * @test
     */
    public function shouldFailCheckIfHostIsInvalid(): void
    {
        $check = new PdoConnectionCheck(
            'mysql',
            $this->getDatabaseHost().'-some',
            $this->getDatabasePort(),
            $this->getDatabaseName(),
            $this->getDatabaseUser(),
            $this->getDatabasePassword()
        );

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Fail connect to database. Error: SQLSTATE[HY000] [2002] php_network_getaddresses:', $result->getMessage());
    }

    /**
     * @test
     */
    public function shouldFailCheckIfCredentialsIsInvalid(): void
    {
        $check = new PdoConnectionCheck(
            'mysql',
            $this->getDatabaseHost(),
            $this->getDatabasePort(),
            $this->getDatabaseName(),
            $this->getDatabaseUser(),
            $this->getDatabasePassword().'-some'
        );

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Fail connect to database. Error: SQLSTATE[HY000] [1045] Access denied for user', $result->getMessage());
    }

    /**
     * @test
     */
    public function shouldFailCheckIfDatabaseIsInvalid(): void
    {
        $check = new PdoConnectionCheck(
            'mysql',
            $this->getDatabaseHost(),
            $this->getDatabasePort(),
            $this->getDatabaseName().'_some',
            $this->getDatabaseUser(),
            $this->getDatabasePassword()
        );

        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to database. Error: SQLSTATE[HY000] [1049] Unknown database \''.$this->getDatabaseName().'_some\''), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParameters(): void
    {
        $check = new PdoConnectionCheck(
            'mysql',
            $this->getDatabaseHost(),
            $this->getDatabasePort(),
            $this->getDatabaseName(),
            $this->getDatabaseUser(),
            $this->getDatabasePassword()
        );

        self::assertEquals([
            'driver' => 'mysql',
            'host'   => $this->getDatabaseHost(),
            'port'   => $this->getDatabasePort(),
            'dbname' => $this->getDatabaseName(),
            'user'   => $this->getDatabaseUser(),
            'pass'   => '***',
        ], $check->getExtraParameters());
    }
}
