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

namespace FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpLib;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\Dsn;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;

readonly class RabbitMqSocketConnectionCheck implements CheckInterface
{
    public function __construct(private Dsn $dsn)
    {
    }

    public function check(): Result
    {
        if (!\extension_loaded('sockets')) {
            return new Failure('ext-sockets is not installed.');
        }

        if (!\class_exists(AMQPSocketConnection::class)) {
            return new Failure('php-amqplib/php-amqplib is not installed.');
        }

        try {
            new AMQPSocketConnection(
                $this->dsn->host,
                $this->dsn->port ?: 5672,
                $this->dsn->username ?: '',
                $this->dsn->password ?: '',
                \urldecode($this->dsn->target)
            );
        } catch (AMQPConnectionClosedException $e) {
            return new Failure($e->getMessage());
        } catch (AMQPIOException $e) {
            return new Failure($e->getMessage());
        }

        return new Success('Successfully connected to RabbitMQ');
    }

    public function getExtraParameters(): array
    {
        return [
            'dsn' => $this->dsn->getDsn(path: true, maskedPassword: true),
        ];
    }
}
