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

namespace FiveLab\Component\Diagnostic\Check\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Exception;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check the connect to database.
 */
class DbalConnectionCheck implements CheckInterface
{
    /**
     * @var DriverConnection
     */
    private DriverConnection $connection;

    /**
     * Constructor.
     *
     * @param DriverConnection $connection
     */
    public function __construct(DriverConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        try {
            $this->connection->executeQuery('SELECT 1');
        } catch (Exception $e) {
            return new Failure(\sprintf(
                'Fail connect to database. Error: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to database. Throw exception: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        return new Success('Success connect to database.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $parameters = [];

        if ($this->connection instanceof Connection) {
            $parameters = [
                'host'   => $this->connection->getHost(),
                'port'   => $this->connection->getPort(),
                'user'   => $this->connection->getUsername(),
                'pass'   => '***',
                'dbname' => $this->connection->getDatabase(),
            ];
        }

        return $parameters;
    }
}
