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
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check connect to RabbitMQ
 */
class RabbitMqConnectionCheck implements CheckInterface
{
    /**
     * @var array
     */
    private $connectionParameters = [];

    /**
     * Constructor.
     *
     * @param array $connectionParameters
     */
    public function __construct(array $connectionParameters)
    {
        if (!\array_key_exists('connect_timeout', $connectionParameters)) {
            $connectionParameters['connect_timeout'] = 5;
        }

        $this->connectionParameters = $connectionParameters;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        if (!\class_exists(\AMQPConnection::class)) {
            return new Failure('The ext-amqp not installed.');
        }

        $connection = new \AMQPConnection($this->connectionParameters);

        try {
            $connection->connect();
        } catch (\AMQPConnectionException $e) {
            return new Failure($e->getMessage());
        }

        return new Success('Success connect to RabbitMQ');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $extra = $this->connectionParameters;

        if (\array_key_exists('password', $extra)) {
            $extra['password'] = '***';
        }

        return $extra;
    }
}
