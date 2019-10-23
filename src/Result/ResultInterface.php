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

/**
 * All results should implement this interface.
 */
interface ResultInterface
{
    /**
     * Get the message
     *
     * @return string
     */
    public function getMessage(): string;
}
