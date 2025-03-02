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

readonly class EnvVariableSkipRegistry implements SkipRegistryInterface
{
    /**
     * @var array<string>
     */
    private array $skipKeys;

    public function __construct(string $envName = 'SKIP_CHECKS')
    {
        $envValue = (string) \getenv($envName);

        $skipKeys = \explode(',', $envValue);

        $skipKeys = \array_map('\trim', $skipKeys);
        $skipKeys = \array_filter($skipKeys);
        $skipKeys = \array_map('\strtolower', $skipKeys);

        $this->skipKeys = $skipKeys;
    }

    public function isShouldBeSkipped(CheckDefinition $definition): bool
    {
        return \in_array($definition->key, $this->skipKeys, true);
    }
}
