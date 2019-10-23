<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Mailer;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check connect to mailer via Swift Mailer.
 */
class SwiftMailerConnectionCheck implements CheckInterface
{
    /**
     * @var \Swift_Mailer
     */
    private $swiftmailer;

    /**
     * Constructor.
     *
     * @param \Swift_Mailer $swiftmailer
     */
    public function __construct(\Swift_Mailer $swiftmailer)
    {
        $this->swiftmailer = $swiftmailer;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        $transport = $this->swiftmailer->getTransport();

        try {
            $transport->start();
            $transport->stop();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect or send HELO command to mailer. Error: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        return new Success('Success connect and send HELO command to mailer.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $transport = $this->swiftmailer->getTransport();

        $params = [
            'transport' => 'not supported',
        ];

        if ($transport instanceof \Swift_Transport_SendmailTransport) {
            $params['transport'] = 'sendmail';
        }

        if ($transport instanceof \Swift_Transport_SpoolTransport) {
            $params['transport'] = 'spool';
        }

        if ($transport instanceof \Swift_Transport_EsmtpTransport) {
            $params = [
                'transport' => 'smtp',
                'host'      => $transport->getHost(),
                'port'      => $transport->getPort(),
            ];
        }

        return $params;
    }
}
