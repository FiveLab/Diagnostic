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

namespace FiveLab\Component\Diagnostic\Tests\Check;

use PHPUnit\Framework\TestCase;

abstract class AbstractRabbitMqTestCase extends TestCase
{
    /**
     * Get host for connect to RabbitMQ
     *
     * @return string
     */
    protected function getRabbitMqHost(): ?string
    {
        return \getenv('RABBITMQ_HOST') ?: null;
    }

    /**
     * Get port for connect to RabbitMQ
     *
     * @return int
     */
    protected function getRabbitMqPort(): int
    {
        return \getenv('RABBITMQ_PORT') ? (int) \getenv('RABBITMQ_PORT') : 5672;
    }

    /**
     * Get RabbitMQ virtual host
     *
     * @return string
     */
    protected function getRabbitMqVhost(): string
    {
        return \getenv('RABBITMQ_VHOST') ?: '/';
    }

    /**
     * Get the login for connect to RabbitMQ
     *
     * @return string
     */
    protected function getRabbitMqLogin(): string
    {
        return \getenv('RABBITMQ_LOGIN') ?: 'guest';
    }

    /**
     * Get the password for connect to RabbitMQ.
     *
     * @return string
     */
    protected function getRabbitMqPassword(): string
    {
        return \getenv('RABBITMQ_PASSWORD') ?: 'guest';
    }

    /**
     * Is can testing with RabbitMQ?
     *
     * @return bool
     */
    protected function canTestingWithRabbitMq(): bool
    {
        return (bool) $this->getRabbitMqHost();
    }
}
