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
use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;

/**
 * Check connect to RabbitMQ
 */
readonly class RabbitMqSocketConnectionCheck implements CheckInterface
{
    /**
     * Constructor.
     *
     * @param RabbitMqConnectionParameters $connectionParameters
     */
    public function __construct(private RabbitMqConnectionParameters $connectionParameters)
    {
    }

    /**
     * {@inheritdoc}
     */
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
                $this->connectionParameters->host,
                $this->connectionParameters->port,
                $this->connectionParameters->username,
                $this->connectionParameters->password,
                $this->connectionParameters->vhost
            );
        } catch (AMQPConnectionClosedException $e) {
            return new Failure($e->getMessage());
        } catch (AMQPIOException $e) {
            return new Failure($e->getMessage());
        }

        return new Success('Successfully connected to RabbitMQ');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'dsn'   => $this->connectionParameters->getDsn(false, true),
            'vhost' => $this->connectionParameters->vhost,
        ];
    }
}
