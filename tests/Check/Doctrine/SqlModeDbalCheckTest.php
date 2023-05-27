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

use Doctrine\DBAL\Connection;
use FiveLab\Component\Diagnostic\Check\Doctrine\SqlModeDbalCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;

class SqlModeDbalCheckTest extends AbstractDoctrineCheckTestCase
{
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @var string
     */
    private string $backupSqlMode;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithDatabase()) {
            self::markTestSkipped('The database not configured.');
        }

        $this->connection = $this->makeDbalConnection();

        $stmt = $this->connection->executeQuery('SELECT @@GLOBAL.sql_mode');

        $this->backupSqlMode = $stmt->fetchFirstColumn()[0];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $stmt = $this->connection->prepare('SET @@GLOBAL.sql_mode = :sql_mode');
        $stmt->bindValue('sql_mode', $this->backupSqlMode);
        $stmt->executeStatement();
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParams(): void
    {
        $connection = $this->makeDbalConnection();

        $check = new SqlModeDbalCheck($connection, ['STRICT_TRANS_TABLES', 'NO_ENGINE_SUBSTITUTION'], ['ONLY_FULL_GROUP_BY']);
        $check->check();

        $extraParams = $check->getExtraParameters();

        self::assertEquals([
            'host'               => $this->getDatabaseHost(),
            'port'               => $this->getDatabasePort(),
            'user'               => $this->getDatabaseUser(),
            'pass'               => '***',
            'dbname'             => $this->getDatabaseName(),
            'actual sql modes'   => \explode(',', $this->backupSqlMode),
            'expected sql modes' => ['STRICT_TRANS_TABLES', 'NO_ENGINE_SUBSTITUTION'],
            'excluded sql modes' => ['ONLY_FULL_GROUP_BY'],
        ], $extraParams);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParamsWithoutCheck(): void
    {
        $connection = $this->makeDbalConnection();

        $check = new SqlModeDbalCheck($connection, ['STRICT_TRANS_TABLES', 'NO_ENGINE_SUBSTITUTION'], ['ONLY_FULL_GROUP_BY']);

        $extraParams = $check->getExtraParameters();

        self::assertEquals([
            'host'               => $this->getDatabaseHost(),
            'port'               => $this->getDatabasePort(),
            'user'               => $this->getDatabaseUser(),
            'pass'               => '***',
            'dbname'             => $this->getDatabaseName(),
            'expected sql modes' => ['STRICT_TRANS_TABLES', 'NO_ENGINE_SUBSTITUTION'],
            'excluded sql modes' => ['ONLY_FULL_GROUP_BY'],
        ], $extraParams);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckWithExpected(): void
    {
        $this->connection->executeStatement('SET @@GLOBAL.sql_mode = \'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION\'');

        $connection = $this->makeDbalConnection();

        $check = new SqlModeDbalCheck($connection, ['STRICT_TRANS_TABLES', 'NO_ENGINE_SUBSTITUTION'], []);
        $result = $check->check();

        self::assertEquals(new Success('All required SQL modes exist.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckWithExpectedAndExcluded(): void
    {
        $this->connection->executeStatement('SET @@GLOBAL.sql_mode = \'NO_ENGINE_SUBSTITUTION\'');

        $connection = $this->makeDbalConnection();

        $check = new SqlModeDbalCheck($connection, ['NO_ENGINE_SUBSTITUTION'], ['STRICT_TRANS_TABLES']);
        $result = $check->check();

        self::assertEquals(new Success('All required SQL modes exist.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckWithMissedRequiredSqlModes(): void
    {
        $this->connection->executeStatement('SET @@GLOBAL.sql_mode = \'NO_ENGINE_SUBSTITUTION\'');

        $connection = $this->makeDbalConnection();

        $check = new SqlModeDbalCheck($connection, ['ONLY_FULL_GROUP_BY', 'STRICT_TRANS_TABLES']);
        $result = $check->check();

        self::assertEquals(new Failure('Missed required SQL modes (ONLY_FULL_GROUP_BY, STRICT_TRANS_TABLES).'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckWithExistExcludedSqlModes(): void
    {
        $this->connection->executeStatement('SET @@GLOBAL.sql_mode = \'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION\'');

        $connection = $this->makeDbalConnection();

        $check = new SqlModeDbalCheck($connection, [], ['STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'ONLY_FULL_GROUP_BY']);
        $result = $check->check();

        self::assertEquals(new Failure('Exist STRICT_TRANS_TABLES, ONLY_FULL_GROUP_BY sql modes, but those modes must be excluded.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfConnectionFail(): void
    {
        $connection = $this->makeDbalConnection([
            'host' => \uniqid(),
        ]);

        $check = new SqlModeDbalCheck($connection, ['STRICT_TRANS_TABLES']);
        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringContainsString('SQLSTATE[HY000] [2002]', $result->getMessage());
    }
}
