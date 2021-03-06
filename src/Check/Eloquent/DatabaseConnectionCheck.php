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

namespace FiveLab\Component\Diagnostic\Check\Eloquent;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check whether we are able to execute any queries against database
 */
class DatabaseConnectionCheck implements CheckInterface
{
    /**
     * @var ConnectionInterface
     */
    private ConnectionInterface $connection;

    /**
     * Constructor.
     *
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function check(): ResultInterface
    {
        try {
            $this->connection->select((string) $this->connection->raw('SELECT 1'));
        } catch (QueryException $e) {
            return new Failure(\sprintf(
                'Failed establishing database connection. Error: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Failed establishing database connection. Exception has been thrown: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        return new Success('Successfully connected to database.');
    }

    /**
     * {@inheritDoc}
     */
    public function getExtraParameters(): array
    {
        $config = $this->connection->getConfig();
        $config['password'] = '***';

        return $config;
    }
}
