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
    protected function getMailerHost(): ?string
    {
        return \getenv('MAILER_HOST') ?: null;
    }

    protected function getMailerPort(): int
    {
        return \getenv('MAILER_PORT') ? (int) \getenv('MAILER_PORT') : 1025;
    }

    public function getDsn(): string
    {
        return \sprintf('smtp://%s:%d', $this->getMailerHost(), $this->getMailerPort());
    }

    protected function canTestingWithMailer(): bool
    {
        return (bool) $this->getMailerHost();
    }
}
