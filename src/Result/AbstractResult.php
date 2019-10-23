<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Result;

/**
 * Helper for create result instances.
 */
abstract class AbstractResult implements ResultInterface
{
    /**
     * @var string
     */
    private $message;

    /**
     * Constructor.
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
