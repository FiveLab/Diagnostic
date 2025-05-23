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
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver as MySqlDriver;
use Doctrine\DBAL\Driver\PDOMySql\Driver as OldMySqlDriver;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractDatabaseTestCase;

abstract class AbstractDoctrineCheckTestCase extends AbstractDatabaseTestCase
{
    protected function setUp(): void
    {
        if (!$this->canTestingWithDatabase()) {
            self::markTestSkipped('The database not configured.');
        }
    }

    protected function makeDbalConnection(array $connectionOptions = []): Connection|DriverConnection
    {
        $connectionOptions = \array_merge($this->getConnectionOptions(), $connectionOptions);

        if (\class_exists(MySqlDriver::class)) {
            return new Connection($connectionOptions, new MySqlDriver());
        }

        return new Connection($connectionOptions, new OldMySqlDriver());
    }

    protected function getConnectionOptions(): array
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
