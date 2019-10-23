<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Definition\Filter;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;

/**
 * Composite filter with implement OR logic.
 */
class OrXFilter
{
    /**
     * @var array|callable[]
     */
    private $filters;

    /**
     * Constructor.
     *
     * @param callable ...$filters
     */
    public function __construct(callable ...$filters)
    {
        $this->filters = $filters;
    }

    /**
     * Filter with OR logic.
     *
     * @param CheckDefinitionInterface $definition
     *
     * @return bool
     */
    public function __invoke(CheckDefinitionInterface $definition): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter($definition)) {
                return true;
            }
        }

        return false;
    }
}
