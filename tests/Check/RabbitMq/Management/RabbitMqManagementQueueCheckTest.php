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

namespace FiveLab\Component\Diagnostic\Tests\Check\RabbitMq\Management;

use FiveLab\Component\Diagnostic\Check\RabbitMq\Management\RabbitMqManagementQueueCheck;
use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRabbitMqTestCase;

class RabbitMqManagementQueueCheckTest extends AbstractRabbitMqTestCase
{
    /**
     * @var string
     */
    private $queueName;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->canTestingWithRabbitMq()) {
            self::markTestSkipped('The RabbitMQ is not configured.');
        }

        $this->queueName = 'test_'.\uniqid((string) \random_int(0, PHP_INT_MAX), true);
        $this->declareQueue($this->queueName);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        if ($this->canTestingWithRabbitMq()) {
            $this->deleteAllQueues();
        }
    }

    /**
     * @test
     */
    public function shouldSuccessCheck(): void
    {
        $connectionParameters = $this->getRabbitMqManagementConnectionParameters();

        $check = new RabbitMqManagementQueueCheck($connectionParameters, $this->queueName);
        $result = $check->check();

        self::assertEquals(new Success('Success check queue via RabbitMQ Management API.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtra(): void
    {
        $connectionParameters = $this->getRabbitMqManagementConnectionParameters();

        $check = new RabbitMqManagementQueueCheck($connectionParameters, $this->queueName);

        self::assertEquals([
            'dsn'   => $connectionParameters->getDsn(true, true),
            'vhost' => $connectionParameters->getVhost(),
            'queue' => $this->queueName,
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldFailIfPasswordIsWrong(): void
    {
        $connectionParameters = new RabbitMqConnectionParameters(
            $this->getRabbitMqHost(),
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            'some-foo-bar',
            $this->getRabbitMqVhost()
        );

        $check = new RabbitMqManagementQueueCheck($connectionParameters, $this->queueName);
        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to RabbitMQ Management API. Return wrong status code - 401.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfHostIsWrong(): void
    {
        $connectionParameters = new RabbitMqConnectionParameters(
            'some-foo-bar',
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword(),
            $this->getRabbitMqVhost()
        );

        $check = new RabbitMqManagementQueueCheck($connectionParameters, $this->queueName);
        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Fail connect to RabbitMQ Management API. Error: cURL error 6: Could not resolve host: some-foo-bar', $result->getMessage());
    }

    /**
     * @test
     */
    public function shouldFailIfQueueNotFound(): void
    {
        $connectionParameters = new RabbitMqConnectionParameters(
            $this->getRabbitMqHost(),
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword(),
            $this->getRabbitMqVhost()
        );

        $check = new RabbitMqManagementQueueCheck($connectionParameters, $this->queueName.'a');
        $result = $check->check();

        self::assertEquals(new Failure('Queue was not found.'), $result);
    }
}
