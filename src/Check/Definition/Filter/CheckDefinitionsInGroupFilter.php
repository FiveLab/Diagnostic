<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Definition\Filter;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;

/**
 * The filter for filtering definitions with contain group.
 */
class CheckDefinitionsInGroupFilter
{
    /**
     * @var string
     */
    private $groupName;

    /**
     * Constructor.
     *
     * @param string $groupName
     */
    public function __construct(string $groupName)
    {
        $this->groupName = $groupName;
    }

    /**
     * Filter check definitions by group name
     *
     * @param CheckDefinitionInterface $definition
     *
     * @return bool
     */
    public function __invoke(CheckDefinitionInterface $definition): bool
    {
        return \in_array($this->groupName, $definition->getGroups(), true);
    }
}
