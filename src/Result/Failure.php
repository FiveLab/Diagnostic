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
 * Failure result
 */
class Failure extends Result
{
    /**
     * Constructor.
     *
     * @param string          $message
     * @param \Throwable|null $error
     */
    public function __construct(string $message, public ?\Throwable $error = null)
    {
        parent::__construct($message);
    }
}
