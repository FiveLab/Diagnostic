<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Definition;

/**
 * Collection for store check definitions.
 */
class DefinitionCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array|CheckDefinitionInterface[]
     */
    private $definitions;

    /**
     * Constructor.
     *
     * @param CheckDefinitionInterface ...$definitions
     */
    public function __construct(CheckDefinitionInterface ...$definitions)
    {
        $this->definitions = $definitions;
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator|CheckDefinitionInterface[]
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->definitions);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->definitions);
    }

    /**
     * Get all groups
     *
     * @return array
     */
    public function getGroups(): array
    {
        $groups = [[]];

        foreach ($this->definitions as $definition) {
            $groups[] = $definition->getGroups();
        }

        $groups = \array_merge(...$groups);
        $groups = \array_unique($groups);

        return $groups;
    }

    /**
     * Filter collection
     *
     * @param callable $filter
     *
     * @return DefinitionCollection
     */
    public function filter(callable $filter): DefinitionCollection
    {
        $filtered = \array_filter($this->definitions, $filter);

        return new self(...$filtered);
    }
}
