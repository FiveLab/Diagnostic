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

/**
 * Composite filter with implement OR logic.
 */
readonly class OrXFilter
{
    /**
     * @var array|callable[]
     */
    private array $filters;

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
     * @param CheckDefinition $definition
     *
     * @return bool
     */
    public function __invoke(CheckDefinition $definition): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter($definition)) {
                return true;
            }
        }

        return false;
    }
}
