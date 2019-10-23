<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check\Mailer;

use FiveLab\Component\Diagnostic\Check\Mailer\SwiftMailerConnectionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractMailerTestCase;

class SwiftMailerConnectionCheckTest extends AbstractMailerTestCase
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

    /**
     * @test
     */
    public function shouldSuccessCheck(): void
    {
        $transport = new \Swift_SmtpTransport(
            $this->getMailerHost(),
            $this->getMailerPort()
        );

        $mailer = new \Swift_Mailer($transport);

        $check = new SwiftMailerConnectionCheck($mailer);

        $result = $check->check();

        self::assertEquals(new Success('Success connect and send HELO command to mailer.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParametersForSmtp(): void
    {
        $transport = new \Swift_SmtpTransport(
            $this->getMailerHost(),
            $this->getMailerPort()
        );

        $mailer = new \Swift_Mailer($transport);

        $check = new SwiftMailerConnectionCheck($mailer);

        self::assertEquals([
            'transport' => 'smtp',
            'host'      => $this->getMailerHost(),
            'port'      => $this->getMailerPort(),
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParametersForSpool(): void
    {
        $transport = new \Swift_Transport_SpoolTransport(new \Swift_Events_SimpleEventDispatcher());
        $mailer = new \Swift_Mailer($transport);

        $check = new SwiftMailerConnectionCheck($mailer);

        self::assertEquals([
            'transport' => 'spool',
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParametersForSendmail(): void
    {
        $transport = $this->createMock(\Swift_Transport_SendmailTransport::class);
        $mailer = new \Swift_Mailer($transport);

        $check = new SwiftMailerConnectionCheck($mailer);

        self::assertEquals([
            'transport' => 'sendmail',
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldFailIfHostIsWrong(): void
    {
        $transport = new \Swift_SmtpTransport(
            $this->getMailerHost().'some',
            $this->getMailerPort()
        );

        $mailer = new \Swift_Mailer($transport);

        $check = new SwiftMailerConnectionCheck($mailer);

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Fail connect or send HELO command to mailer. Error: Connection could not be established with host', $result->getMessage());
    }
}
