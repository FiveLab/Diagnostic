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

namespace FiveLab\Component\Diagnostic\Tests\Check\RabbitMq\AmqpExt;

use FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpExt\RabbitMqConnectionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRabbitMqTestCase;

class RabbitMqConnectionCheckTest extends AbstractRabbitMqTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithRabbitMq()) {
            self::markTestSkipped('The RabbitMQ is not configured.');
        }

        if (!\class_exists(\AMQPConnection::class)) {
            self::markTestSkipped('The ext-amqp not installed.');
        }
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParams(): void
    {
        $check = new RabbitMqConnectionCheck([
            'host'     => $this->getRabbitMqHost(),
            'port'     => $this->getRabbitMqPort(),
            'vhost'    => $this->getRabbitMqVhost(),
            'login'    => $this->getRabbitMqLogin(),
            'password' => $this->getRabbitMqPassword(),
        ]);

        self::assertEquals([
            'host'            => $this->getRabbitMqHost(),
            'port'            => $this->getRabbitMqPort(),
            'vhost'           => $this->getRabbitMqVhost(),
            'login'           => $this->getRabbitMqLogin(),
            'password'        => '***',
            'connect_timeout' => 5,
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldReturnOkIfSuccessConnect(): void
    {
        $options = [
            'host'     => $this->getRabbitMqHost(),
            'port'     => $this->getRabbitMqPort(),
            'vhost'    => $this->getRabbitMqVhost(),
            'login'    => $this->getRabbitMqLogin(),
            'password' => $this->getRabbitMqPassword(),
        ];

        $check = new RabbitMqConnectionCheck($options);

        $result = $check->check();

        self::assertEquals(new Success('Success connect to RabbitMQ'), $result);
    }

    /**
     * @test
     */
    public function shouldReturnFailIfPasswordIsWrong(): void
    {
        $options = [
            'host'     => $this->getRabbitMqHost(),
            'port'     => $this->getRabbitMqPort(),
            'vhost'    => $this->getRabbitMqVhost(),
            'login'    => $this->getRabbitMqLogin(),
            'password' => \uniqid(),
        ];

        $check = new RabbitMqConnectionCheck($options);

        $result = $check->check();

        self::assertEquals(
            new Failure('Server connection error: 403, message: ACCESS_REFUSED - Login was refused using authentication mechanism PLAIN. For details see the broker logfile. - Potential login failure.'),
            $result
        );
    }

    /**
     * @test
     */
    public function shouldFailIfHostIsDown(): void
    {
        $options = [
            'host'     => \uniqid(),
            'port'     => $this->getRabbitMqPort(),
            'vhost'    => $this->getRabbitMqVhost(),
            'login'    => $this->getRabbitMqLogin(),
            'password' => $this->getRabbitMqPassword(),
        ];

        $check = new RabbitMqConnectionCheck($options);

        $result = $check->check();

        self::assertEquals(
            new Failure('Socket error: could not connect to host.'),
            $result
        );
    }
}
