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

use FiveLab\Component\Diagnostic\Check\RabbitMq\Management\RabbitMqManagementCheck;
use FiveLab\Component\Diagnostic\Check\RabbitMq\Management\RabbitMqManagementVersionCheck;
use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRabbitMqTestCase;
use PHPUnit\Framework\Attributes\Test;

class RabbitMqManagementVersionCheckTest extends AbstractRabbitMqTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->canTestingWithRabbitMq()) {
            self::markTestSkipped('The RabbitMQ is not configured.');
        }
    }

    #[Test]
    public function shouldSuccessCheck(): void
    {
        $connectionParameters = $this->getRabbitMqManagementConnectionParameters();

        $check = new RabbitMqManagementVersionCheck($connectionParameters, '~3.13.0');
        $result = $check->check();

        self::assertEquals(new Success('Success check RabbitMQ version.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtra(): void
    {
        $connectionParameters = $this->getRabbitMqManagementConnectionParameters();

        $check = new RabbitMqManagementVersionCheck($connectionParameters, '~3.13.0');
        $check->check();

        $extraParams = $check->getExtraParameters();

        self::assertCount(3, $extraParams);

        self::assertEquals($connectionParameters->getDsn(true, true), $extraParams['dsn']);
        self::assertEquals('~3.13.0', $extraParams['expected version']);
        self::assertStringStartsWith('3.13.', $extraParams['actual version']);
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

        $check = new RabbitMqManagementCheck($connectionParameters);
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

        $check = new RabbitMqManagementCheck($connectionParameters);
        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Fail connect to RabbitMQ Management API. Error: cURL error 6: Could not resolve host: some-foo-bar', $result->message);
    }

    #[Test]
    public function shouldFailIfVersionNotSatisfied(): void
    {
        $connectionParameters = $this->getRabbitMqManagementConnectionParameters();

        $check = new RabbitMqManagementVersionCheck($connectionParameters, '~3.12.0');
        $result = $check->check();

        self::assertEquals(new Failure('Fail check RabbitMQ version.'), $result);
    }
}
