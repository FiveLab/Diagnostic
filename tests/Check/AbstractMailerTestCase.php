<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check;

use PHPUnit\Framework\TestCase;

abstract class AbstractMailerTestCase extends TestCase
{
    /**
     * Get mailer host
     *
     * @return string|null
     */
    protected function getMailerHost(): ?string
    {
        return \getenv('MAILER_HOST') ?: null;
    }

    /**
     * Get the mailer port
     *
     * @return int
     */
    protected function getMailerPort(): int
    {
        return \getenv('MAILER_PORT') ? (int) \getenv('MAILER_PORT') : 1025;
    }

    /**
     * Is can testing with mailer?
     *
     * @return bool
     */
    protected function canTestingWithMailer(): bool
    {
        return (bool) $this->getMailerHost();
    }
}
