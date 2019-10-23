<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\DependencyInjection;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\ResultInterface;

class StubCheck implements CheckInterface
{
    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        throw new \BadMethodCallException();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        throw new \BadMethodCallException();
    }
}
