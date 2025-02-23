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

namespace FiveLab\Component\Diagnostic\Runner\Skip;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinition;

interface SkipRegistryInterface
{
    /**
     * Is the check should be skipped?
     *
     * @param CheckDefinition $definition
     *
     * @return bool
     */
    public function isShouldBeSkipped(CheckDefinition $definition): bool;
}
