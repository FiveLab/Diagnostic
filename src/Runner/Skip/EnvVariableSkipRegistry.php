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

    /**
     * @var array<string>
     */
    private array $skipGroups;

    public function __construct(string $envName = 'SKIP_CHECKS', string $groupsEnvName = 'SKIP_CHECK_GROUPS')
    {
        $this->skipKeys = $this->parseEnv($envName);
        $this->skipGroups = $this->parseEnv($groupsEnvName);
    }

    public function isShouldBeSkipped(CheckDefinition $definition): bool
    {
        if (\in_array($definition->key, $this->skipKeys, true)) {
            return true;
        }

        foreach ($this->skipGroups as $skipGroup) {
            if (\in_array($skipGroup, $definition->groups, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse ENV as string and return elements as array.
     *
     * @param string $env
     *
     * @return array<string>
     */
    private function parseEnv(string $env): array
    {
        $parts = \explode(',', (string) \getenv($env));
        $parts = \array_map('\trim', $parts);
        $parts = \array_filter($parts);

        return \array_map('\strtolower', $parts);
    }
}
