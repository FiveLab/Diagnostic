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
 * Emit this event before run check.
 */
class BeforeRunCheckEvent extends Event
{
    /**
     * @var Result|null
     */
    private ?Result $result = null;

    /**
     * Constructor.
     *
     * @param CheckDefinition $definition
     */
    public function __construct(public readonly CheckDefinition $definition)
    {
    }

    /**
     * Force set the result
     *
     * @param Result $result
     */
    public function setResult(Result $result): void
    {
        $this->result = $result;

        $this->stopPropagation();
    }

    /**
     * Get the result
     *
     * @return Result|null
     */
    public function getResult(): ?Result
    {
        return $this->result;
    }
}
