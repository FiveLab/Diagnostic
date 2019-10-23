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

/**
 * All check definitions should implement this interface.
 */
interface CheckDefinitionInterface
{
    /**
     * Get the unique key of check
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Get the check
     *
     * @return CheckInterface
     */
    public function getCheck(): CheckInterface;

    /**
     * Get group names of this check
     *
     * @return array
     */
    public function getGroups(): array;
}
