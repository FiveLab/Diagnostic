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

namespace FiveLab\Component\Diagnostic\Runner;

use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollection;

/**
 * All runners should implement this interface.
 */
interface RunnerInterface
{
    /**
     * Run the diagnostic.
     *
     * @param DefinitionCollection $definitions
     *
     * @return bool Returns TRUE if all success and FALSE is anyone check is failure.
     */
    public function run(DefinitionCollection $definitions): bool;
}
