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

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Emit this event after complete run check.
 */
class CompleteRunCheckEvent extends Event
{
    /**
     * @var CheckDefinitionInterface
     */
    private $definition;

    /**
     * @var ResultInterface
     */
    private $result;

    /**
     * Constructor.
     *
     * @param CheckDefinitionInterface $definition
     * @param ResultInterface          $result
     */
    public function __construct(CheckDefinitionInterface $definition, ResultInterface $result)
    {
        $this->definition = $definition;
        $this->result = $result;
    }

    /**
     * Get definition
     *
     * @return CheckDefinitionInterface
     */
    public function getDefinition(): CheckDefinitionInterface
    {
        return $this->definition;
    }

    /**
     * Get the result
     *
     * @return ResultInterface
     */
    public function getResult(): ResultInterface
    {
        return $this->result;
    }
}
