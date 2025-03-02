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

namespace FiveLab\Component\Diagnostic\Tests\DependencyInjection;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Result;

class StubCheck implements CheckInterface
{
    public function check(): Result
    {
        throw new \BadMethodCallException();
    }

    public function getExtraParameters(): array
    {
        throw new \BadMethodCallException();
    }
}
