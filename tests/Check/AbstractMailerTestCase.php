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
     * Get dsn
     *
     * @return string
     */
    public function getDsn(): string
    {
        return \sprintf('smtp://%s:%d', $this->getMailerHost(), $this->getMailerPort());
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
