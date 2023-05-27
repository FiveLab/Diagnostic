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

use FiveLab\Component\Diagnostic\Check\RabbitMq\Management\RabbitMqManagementExchangeCheck;
use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRabbitMqTestCase;
use PHPUnit\Framework\Attributes\Test;

class RabbitMqManagementExchangeCheckTest extends AbstractRabbitMqTestCase
{
    /**
     * @var string|null
     */
    private ?string $exchangeName = null;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->canTestingWithRabbitMq()) {
            self::markTestSkipped('The RabbitMQ is not configured.');
        }

        $this->exchangeName = 'test_'.\uniqid((string) \random_int(0, PHP_INT_MAX), true);
        $this->declareExchange($this->exchangeName, 'direct');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        if ($this->canTestingWithRabbitMq()) {
            $this->deleteAllExchanges();
        }
    }

    #[Test]
    public function shouldSuccessCheck(): void
    {
        $connectionParameters = $this->getRabbitMqManagementConnectionParameters();

        $check = new RabbitMqManagementExchangeCheck($connectionParameters, $this->exchangeName, 'direct');
        $result = $check->check();

        self::assertEquals(new Success('Success check exchange via RabbitMQ Management API.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtra(): void
    {
        $connectionParameters = $this->getRabbitMqManagementConnectionParameters();

        $check = new RabbitMqManagementExchangeCheck($connectionParameters, $this->exchangeName, 'direct');

        self::assertEquals([
            'dsn'         => $connectionParameters->getDsn(true, true),
            'vhost'       => $connectionParameters->getVhost(),
            'exchange'    => $this->exchangeName,
            'type'        => 'direct',
            'actual type' => null,
        ], $check->getExtraParameters());
    }

    #[Test]
    public function shouldFailIfPasswordIsWrong(): void
    {
        $connectionParameters = new RabbitMqConnectionParameters(
            $this->getRabbitMqHost(),
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            'some-foo-bar',
            $this->getRabbitMqVhost()
        );

        $check = new RabbitMqManagementExchangeCheck($connectionParameters, $this->exchangeName, 'direct');
        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to RabbitMQ Management API. Return wrong status code - 401.'), $result);
    }

    #[Test]
    public function shouldFailIfHostIsWrong(): void
    {
        $connectionParameters = new RabbitMqConnectionParameters(
            'some-foo-bar',
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword(),
            $this->getRabbitMqVhost()
        );

        $check = new RabbitMqManagementExchangeCheck($connectionParameters, $this->exchangeName, 'direct');
        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Fail connect to RabbitMQ Management API. Error: cURL error 6: Could not resolve host: some-foo-bar', $result->getMessage());
    }

    #[Test]
    public function shouldFailIfExchangeNotFound(): void
    {
        $connectionParameters = $this->getRabbitMqManagementConnectionParameters();

        $check = new RabbitMqManagementExchangeCheck($connectionParameters, $this->exchangeName.'a', 'direct');
        $result = $check->check();

        self::assertEquals(new Failure('Exchange was not found.'), $result);
    }

    #[Test]
    public function shouldFailIfTypeIsInvalid(): void
    {
        $connectionParameters = $this->getRabbitMqManagementConnectionParameters();

        $check = new RabbitMqManagementExchangeCheck($connectionParameters, $this->exchangeName, 'fanout');
        $result = $check->check();

        self::assertEquals(new Failure('Invalid exchange types.'), $result);

        self::assertEquals([
            'dsn'         => $connectionParameters->getDsn(true, true),
            'vhost'       => '/',
            'exchange'    => $this->exchangeName,
            'type'        => 'fanout',
            'actual type' => 'direct',
        ], $check->getExtraParameters());
    }
}
