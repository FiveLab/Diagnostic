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
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\HttpSecurityEncoder;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport;

readonly class SymfonyMailerSmtpConnectionCheck implements CheckInterface
{
    /**
     * Constructor.
     *
     * @param string                   $dsn
     * @param array<int>               $codes
     * @param HttpSecurityEncoder|null $securityEncoder
     */
    public function __construct(
        private string               $dsn,
        private array                $codes = [220, 250],
        private ?HttpSecurityEncoder $securityEncoder = null
    ) {
    }

    public function check(): Result
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

        // phpcs:ignore FiveLab.Strings.String.DoubleQuotes
        $command = \sprintf("HELO %s\r\n", $transport->getLocalDomain());

        try {
            $transport->getStream()->initialize();
            $transport->executeCommand($command, $this->codes);
        } catch (TransportException $e) {
            return new Failure(\sprintf(
                'Fail connect or send HELO command to mailer. Error: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        } finally {
            try {
                // phpcs:ignore FiveLab.Strings.String.DoubleQuotes
                $transport->executeCommand("QUIT\r\n", []);
            } catch (\Throwable $error) {
                // Nothing action.
            }

            $transport->getStream()->terminate();
        }

        return new Success('Success connect and send HELO command to mailer.');
    }

    public function getExtraParameters(): array
    {
        $encoder = $this->securityEncoder ?: new HttpSecurityEncoder();

        return [
            'dsn' => $encoder->encodeUri($this->dsn),
        ];
    }
}
