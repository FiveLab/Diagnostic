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
 * @implements \IteratorAggregate<CheckDefinition>
 */
readonly class CheckDefinitions implements \IteratorAggregate, \Countable
{
    /**
     * @var array<CheckDefinition>
     */
    private array $definitions;

    public function __construct(CheckDefinition ...$definitions)
    {
        \array_reduce($definitions, static function (array $definitionKeys, CheckDefinition $definition): array {
            if (\in_array($definition->key, $definitionKeys, true)) {
                throw new \RuntimeException(\sprintf('Duplicate definition with key "%s"', $definition->key));
            }

            if ($definition->key) {
                $definitionKeys[] = $definition->key;
            }

            return $definitionKeys;
        }, []);

        $this->definitions = $definitions;
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator<int, CheckDefinition>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->definitions);
    }

    public function count(): int
    {
        return \count($this->definitions);
    }

    /**
     * Get unique groups
     *
     * @return array<string>
     */
    public function getGroups(): array
    {
        $groups = [[]];

        foreach ($this->definitions as $definition) {
            $groups[] = $definition->groups;
        }

        $groups = \array_merge(...$groups);

        return \array_unique($groups);
    }

    public function filter(callable $filter): CheckDefinitions
    {
        $filtered = \array_filter($this->definitions, $filter);

        return new self(...$filtered);
    }
}
