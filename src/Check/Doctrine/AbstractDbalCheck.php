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
use FiveLab\Component\Diagnostic\Check\CheckInterface;

abstract class AbstractDbalCheck implements CheckInterface
{
    public function __construct(protected readonly Connection|DriverConnection $connection)
    {
    }

    public function getExtraParameters(): array
    {
        $parameters = [];

        if ($this->connection instanceof Connection) {
            if (\method_exists($this->connection, 'getParams')) { // @phpstan-ignore-line
                $parameters = $this->connection->getParams();
                unset($parameters['password']);
            } else {
                $parameters = [
                    'host'   => $this->connection->getHost(),
                    'port'   => $this->connection->getPort(),
                    'user'   => $this->connection->getUsername(),
                    'dbname' => $this->connection->getDatabase(),
                ];
            }

            $parameters['pass'] = '***';
        }

        return $parameters;
    }
}
