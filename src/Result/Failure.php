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

namespace FiveLab\Component\Diagnostic\Result;

readonly class Failure extends Result
{
    public function __construct(string $message, public ?\Throwable $error = null)
    {
        parent::__construct($message);
    }
}
