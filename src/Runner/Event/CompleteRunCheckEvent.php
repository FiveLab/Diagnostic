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

namespace FiveLab\Component\Diagnostic\Runner\Event;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinition;
use FiveLab\Component\Diagnostic\Result\Result;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Emit this event after complete run check.
 */
class CompleteRunCheckEvent extends Event
{
    /**
     * Constructor.
     *
     * @param CheckDefinition $definition
     * @param Result          $result
     */
    public function __construct(public readonly CheckDefinition $definition, public readonly Result $result)
    {
    }
}
