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

namespace FiveLab\Component\Diagnostic\Check\Definition;

/**
 * Collection for store check definitions.
 *
 * @implements \IteratorAggregate<CheckDefinitionInterface>
 */
class DefinitionCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array|CheckDefinitionInterface[]
     */
    private array $definitions;

    /**
     * Constructor.
     *
     * @param CheckDefinitionInterface ...$definitions
     */
    public function __construct(CheckDefinitionInterface ...$definitions)
    {
        \array_reduce($definitions, static function (array $definitionKeys, CheckDefinitionInterface $definition): array {
            if (\in_array($definition->getKey(), $definitionKeys, true)) {
                throw new \RuntimeException(\sprintf('Duplicate definition with key "%s"', $definition->getKey()));
            }

            if ($definition->getKey()) {
                $definitionKeys[] = $definition->getKey();
            }

            return $definitionKeys;
        }, []);

        $this->definitions = $definitions;
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator<int, CheckDefinitionInterface>
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
     * @return array<string>
     */
    public function getGroups(): array
    {
        $groups = [[]];

        foreach ($this->definitions as $definition) {
            $groups[] = $definition->getGroups();
        }

        $groups = \array_merge(...$groups);

        return \array_unique($groups);
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
