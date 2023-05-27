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

namespace FiveLab\Component\Diagnostic\Check;

use FiveLab\Component\Diagnostic\Result\Result;

/**
 * All checks should implement this interface.
 */
interface CheckInterface
{
    /**
     * Run the check
     *
     * @return Result
     */
    public function check(): Result;

    /**
     * Get extra parameters of check. The url as an example.
     *
     * @return array<string, mixed>
     */
    public function getExtraParameters(): array;
}
