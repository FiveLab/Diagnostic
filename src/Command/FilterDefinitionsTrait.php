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

namespace FiveLab\Component\Diagnostic\Command;

use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollection;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\CheckDefinitionsInGroupFilter;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\OrXFilter;

/**
 * The common trait for filter definitions
 */
trait FilterDefinitionsTrait
{
    /**
     * Filter definitions by group
     *
     * @param DefinitionCollection $definitions
     * @param array                $groups
     *
     * @return DefinitionCollection
     */
    protected function filterDefinitionsByGroupInInput(DefinitionCollection $definitions, array $groups): DefinitionCollection
    {
        if ($groups) {
            $notExistenceGroups = \array_diff($groups, $definitions->getGroups());

            if (\count($notExistenceGroups)) {
                throw new \InvalidArgumentException(\sprintf(
                    'The groups "%s" is not configured in your definitions.',
                    \implode('", "', $notExistenceGroups)
                ));
            }

            $filters = \array_map(static function (string $group) {
                return new CheckDefinitionsInGroupFilter($group);
            }, $groups);

            $filter = new OrXFilter(...$filters);

            $definitions = $definitions->filter($filter);
        }

        return $definitions;
    }
}
