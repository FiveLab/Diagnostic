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

namespace FiveLab\Component\Diagnostic\Check\Mailer;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport;

class SymfonyMailerSmtpConnectionCheck implements CheckInterface
{
    /**
     * @var string
     */
    private string $dsn;

    /**
     * Constructor.
     *
     * @param string $dsn
     */
    public function __construct(string $dsn)
    {
        $this->dsn = $dsn;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        if (!\class_exists(Transport::class)) {
            return new Failure('The package "symfony/mailer" is not installed.');
        }

        $transport = Transport::fromDsn($this->dsn);

        if (!$transport instanceof Transport\Smtp\SmtpTransport) {
            return new Failure(\sprintf(
                'Wrong mailer transport. Expected SmtpTransport, but receive "%s" via dsn.',
                \get_class($transport)
            ));
        }

        $command = sprintf("HELO %s\r\n", $transport->getLocalDomain());

        try {
            $transport->getStream()->initialize();
            $transport->executeCommand($command, [250]);
        } catch (TransportException $e) {
            return new Failure(\sprintf(
                'Fail connect or send HELO command to mailer. Error: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        } finally {
            try {
                $transport->executeCommand("QUIT\r\n", []);
            } catch (\Throwable $error) {
                // Nothing action.
            }

            $transport->getStream()->terminate();
        }

        return new Success('Success connect and send HELO command to mailer.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'dsn' => $this->dsn,
        ];
    }
}
