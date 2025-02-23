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

namespace FiveLab\Component\Diagnostic\Check\Definition\Filter;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinition;

readonly class CheckDefinitionsInGroupFilter
{
    public function __construct(private string $groupName)
    {
    }

    public function __invoke(CheckDefinition $definition): bool
    {
        return \in_array($this->groupName, $definition->groups, true);
    }
}
