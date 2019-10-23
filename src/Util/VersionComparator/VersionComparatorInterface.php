<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Util\VersionComparator;

/**
 * All version comparators should implement this interface.
 */
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
