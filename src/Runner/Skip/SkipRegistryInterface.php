<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Runner\Skip;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;

/**
 * All skip registries should implement this interface.
 */
interface SkipRegistryInterface
{
    /**
     * Is the check should be skipped?
     *
     * @param CheckDefinitionInterface $definition
     *
     * @return bool
     */
    public function isShouldBeSkipped(CheckDefinitionInterface $definition): bool;
}
