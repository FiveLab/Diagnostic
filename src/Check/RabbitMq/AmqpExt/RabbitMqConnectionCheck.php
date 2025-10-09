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

namespace FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpExt;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\Dsn;

readonly class RabbitMqConnectionCheck implements CheckInterface
{
    public function __construct(private Dsn $dsn)
    {
    }

    public function check(): Result
    {
        if (!\class_exists(\AMQPConnection::class)) {
            return new Failure('The ext-amqp not installed.');
        }

        $connectionParameters = [
            'host'            => $this->dsn->host,
            'port'            => $this->dsn->port,
            'vhost'           => \urldecode($this->dsn->target),
            'login'           => $this->dsn->username,
            'password'        => $this->dsn->password,
            'connect_timeout' => $this->dsn->options['connect_timeout'] ?? 5,
        ];

        $connection = new \AMQPConnection($connectionParameters);

        try {
            $connection->connect();
        } catch (\AMQPConnectionException $e) {
            return new Failure($e->getMessage());
        }

        return new Success('Success connect to RabbitMQ');
    }

    public function getExtraParameters(): array
    {
        return [
            'dsn' => $this->dsn->getDsn(path: true, maskedPassword: true),
        ];
    }
}
