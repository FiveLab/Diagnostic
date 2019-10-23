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

namespace FiveLab\Component\Diagnostic\Tests\Check\Eloquent;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;
use FiveLab\Component\Diagnostic\Check\Eloquent\DatabaseConnectionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractDatabaseTestCase;

class DatabaseConnectionCheckTest extends AbstractDatabaseTestCase
{
    /**
     * {@inheritDoc}
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
        $connection = $this->getConnection($this->getConnectionOptions());

        $check = new DatabaseConnectionCheck($connection);

        $result = $check->check();

        self::assertEquals(new Success('Successfully connected to database.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParameters(): void
    {
        $connection = $this->getConnection($this->getConnectionOptions());

        $check = new DatabaseConnectionCheck($connection);

        self::assertEquals([
            'driver'   => 'mysql',
            'host'     => $this->getDatabaseHost(),
            'port'     => $this->getDatabasePort(),
            'username' => $this->getDatabaseUser(),
            'password' => '***',
            'database' => $this->getDatabaseName(),
            'prefix'   => '',
            'name'     => 'default',
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldFailIfCredentialsIsWrong(): void
    {
        $options = $this->getConnectionOptions();
        $options['password'] = \uniqid();

        $connection = $this->getConnection($options);

        $check = new DatabaseConnectionCheck($connection);

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringContainsString(
            'SQLSTATE[HY000] [1045]',
            $result->getMessage()
        );
    }

    /**
     * @test
     */
    public function shouldFailIfHostIsInvalid(): void
    {
        $options = $this->getConnectionOptions();
        $options['host'] = \uniqid();

        $connection = $this->getConnection($options);

        $check = new DatabaseConnectionCheck($connection);

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringContainsString(
            'SQLSTATE[HY000] [2002]',
            $result->getMessage()
        );
    }

    /**
     * Get connection options
     *
     * @return array
     */
    private function getConnectionOptions(): array
    {
        return [
            'driver'   => 'mysql',
            'host'     => $this->getDatabaseHost(),
            'port'     => $this->getDatabasePort(),
            'database' => $this->getDatabaseName(),
            'username' => $this->getDatabaseUser(),
            'password' => $this->getDatabasePassword(),
        ];
    }

    /**
     * @param array $options
     *
     * @return ConnectionInterface
     */
    private function getConnection(array $options): ConnectionInterface
    {
        $capsule = new Capsule();
        $capsule->addConnection($options);

        return $capsule->getConnection();
    }
}
