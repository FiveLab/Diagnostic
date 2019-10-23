<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check;

use FiveLab\Component\Diagnostic\Result\ResultInterface;

/**
 * All checks should implement this interface.
 */
interface CheckInterface
{
    /**
     * Run the check
     *
     * @return ResultInterface
     */
    public function check(): ResultInterface;

    /**
     * Get extra parameters of check. The url as an example.
     *
     * @return array
     */
    public function getExtraParameters(): array;
}
