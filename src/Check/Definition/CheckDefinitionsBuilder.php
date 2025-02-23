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

use FiveLab\Component\Diagnostic\Check\CheckInterface;

class CheckDefinitionsBuilder
{
    /**
     * @var array<string, array{"key": string, "check": CheckInterface, "groups": array<string>, "error_on_failure": bool}>
     */
    private array $definitions = [];

    /**
     * Add check
     *
     * @param string               $key
     * @param CheckInterface       $check
     * @param array<string>|string $groups
     * @param bool                 $errorOnFailure
     */
    public function addCheck(string $key, CheckInterface $check, array|string $groups = [], bool $errorOnFailure = true): void
    {
        $groups = (array) $groups;

        $groups = \array_unique($groups);
        $groups = \array_filter($groups);
        $groups = \array_values($groups);

        $cacheKey = $key.\spl_object_hash($check);

        if (\array_key_exists($cacheKey, $this->definitions)) {
            $this->definitions[$cacheKey]['groups'] = \array_merge($this->definitions[$cacheKey]['groups'], $groups);
        } else {
            $this->definitions[$cacheKey] = [
                'key'              => $key,
                'check'            => $check,
                'groups'           => $groups,
                'error_on_failure' => $errorOnFailure,
            ];
        }
    }

    public function build(): CheckDefinitions
    {
        $definitions = [];

        foreach ($this->definitions as $entry) {
            $definitions[] = new CheckDefinition($entry['key'], $entry['check'], $entry['groups'], $entry['error_on_failure']);
        }

        return new CheckDefinitions(...$definitions);
    }
}
