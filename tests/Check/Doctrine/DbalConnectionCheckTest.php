<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use FiveLab\Component\Diagnostic\Check\Doctrine\DbalConnectionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractDatabaseTestCase;

class DbalConnectionCheckTest extends AbstractDatabaseTestCase
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
        $connection = new Connection($this->getConnectionOptions(), new Driver());

        $check = new DbalConnectionCheck($connection);

        $result = $check->check();

        self::assertEquals(new Success('Success connect to database.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParameters(): void
    {
        $connection = new Connection($this->getConnectionOptions(), new Driver());

        $check = new DbalConnectionCheck($connection);

        self::assertEquals([
            'host'   => $this->getDatabaseHost(),
            'port'   => $this->getDatabasePort(),
            'user'   => $this->getDatabaseUser(),
            'pass'   => '***',
            'dbname' => $this->getDatabaseName(),
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldFailIfCredentialsIsWrong(): void
    {
        $options = $this->getConnectionOptions();
        $options['password'] = \uniqid();

        $connection = new Connection($options, new Driver());

        $check = new DbalConnectionCheck($connection);

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

        $connection = new Connection($options, new Driver());

        $check = new DbalConnectionCheck($connection);

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
            'host'     => $this->getDatabaseHost(),
            'port'     => $this->getDatabasePort(),
            'dbname'   => $this->getDatabaseName(),
            'user'     => $this->getDatabaseUser(),
            'password' => $this->getDatabasePassword(),
        ];
    }
}
