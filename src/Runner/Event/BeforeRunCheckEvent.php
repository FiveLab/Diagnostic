<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Runner\Event;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Emit this event before run check.
 */
class BeforeRunCheckEvent extends Event
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
     */
    public function __construct(CheckDefinitionInterface $definition)
    {
        $this->definition = $definition;
    }

    /**
     * Get check definition
     *
     * @return CheckDefinitionInterface
     */
    public function getDefinition(): CheckDefinitionInterface
    {
        return $this->definition;
    }

    /**
     * Force set the result
     *
     * @param ResultInterface $result
     */
    public function setResult(ResultInterface $result): void
    {
        $this->result = $result;

        $this->stopPropagation();
    }

    /**
     * Get the result
     *
     * @return ResultInterface|null
     */
    public function getResult(): ?ResultInterface
    {
        return $this->result;
    }
}
