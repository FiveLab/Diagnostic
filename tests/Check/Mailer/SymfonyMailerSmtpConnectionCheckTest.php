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

namespace FiveLab\Component\Diagnostic\Tests\Check\Mailer;

use FiveLab\Component\Diagnostic\Check\Mailer\SymfonyMailerSmtpConnectionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractMailerTestCase;
use PHPUnit\Framework\Attributes\Test;

class SymfonyMailerSmtpConnectionCheckTest extends AbstractMailerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithMailer()) {
            self::markTestSkipped('The swiftmailer is not configured.');
        }
    }

    #[Test]
    public function shouldSuccessCheck(): void
    {
        $check = new SymfonyMailerSmtpConnectionCheck($this->getDsn());

        $result = $check->check();

        self::assertEquals(new Success('Success connect and send HELO command to mailer.'), $result);
    }

    #[Test]
    public function shouldFailIfHostIsWrong(): void
    {
        $check = new SymfonyMailerSmtpConnectionCheck('smtp://'.$this->getMailerHost().'-some:'.$this->getMailerPort());

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Fail connect or send HELO command to mailer. Error:', $result->message);
    }

    #[Test]
    public function shouldSuccessGetExtra(): void
    {
        $check = new SymfonyMailerSmtpConnectionCheck('smtp://foo-bar:1025?username=some');

        self::assertEquals([
            'dsn' => 'smtp://foo-bar:1025?username=some',
        ], $check->getExtraParameters());
    }
}
