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

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;

/**
 * The registry for read skip checks from ENV variable.
 */
class EnvVariableSkipRegistry implements SkipRegistryInterface
{
    /**
     * @var array<string>
     */
    private array $skipKeys = [];

    /**
     * Constructor.
     *
     * @param string $envName
     */
    public function __construct(string $envName = 'SKIP_CHECKS')
    {
        if ($envValue = \getenv($envName)) {
            $skipKeys = \explode(',', $envValue);

            $skipKeys = \array_map('\trim', $skipKeys);
            $skipKeys = \array_filter($skipKeys);
            $skipKeys = \array_map('\strtolower', $skipKeys);

            $this->skipKeys = $skipKeys;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isShouldBeSkipped(CheckDefinitionInterface $definition): bool
    {
        return \in_array($definition->getKey(), $this->skipKeys, true);
    }
}
