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
 * Default check definition.
 */
readonly class CheckDefinition
{
    /**
     * Constructor.
     *
     * @param string         $key
     * @param CheckInterface $check
     * @param array<string>  $groups
     * @param bool           $errorOnFailure
     */
    public function __construct(
        public string         $key,
        public CheckInterface $check,
        public array          $groups,
        public bool           $errorOnFailure = true
    ) {
    }
}
