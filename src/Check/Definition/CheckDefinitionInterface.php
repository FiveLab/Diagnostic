<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Definition;

use FiveLab\Component\Diagnostic\Check\CheckInterface;

/**
 * All check definitions should implement this interface.
 */
interface CheckDefinitionInterface
{
    /**
     * Get the unique key of check
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Get the check
     *
     * @return CheckInterface
     */
    public function getCheck(): CheckInterface;

    /**
     * Get group names of this check
     *
     * @return array
     */
    public function getGroups(): array;
}
