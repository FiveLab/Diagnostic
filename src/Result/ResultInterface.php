<?php

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
