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

interface VersionComparatorInterface
{
    /**
     * Is version satisfies?
     *
     * @param string $version
     * @param string $expectedVersion
     *
     * @return bool
     */
    public function satisfies(string $version, string $expectedVersion): bool;
}
