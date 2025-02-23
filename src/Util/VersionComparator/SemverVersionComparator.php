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

namespace FiveLab\Component\Diagnostic\Util\VersionComparator;

use Composer\Semver\Semver;

readonly class SemverVersionComparator implements VersionComparatorInterface
{
    public function __construct()
    {
        if (!\class_exists(Semver::class)) {
            throw new \RuntimeException('Cannot use SemverVersionComparator. The package "composer/semver" not installed.');
        }
    }

    public function satisfies(string $version, string $expectedVersion): bool
    {
        return Semver::satisfies($version, $expectedVersion);
    }
}
