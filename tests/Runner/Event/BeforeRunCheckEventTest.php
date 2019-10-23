<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Runner\Event;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Runner\Event\BeforeRunCheckEvent;
use PHPUnit\Framework\TestCase;

class BeforeRunCheckEventTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $definition = $this->createMock(CheckDefinitionInterface::class);

        $event = new BeforeRunCheckEvent($definition);

        self::assertEquals($definition, $event->getDefinition());
        self::assertNull($event->getResult());
    }

    /**
     * @test
     */
    public function shouldStopPropagationIfResultIsSet(): void
    {
        /** @var CheckDefinitionInterface $definition */
        $definition = $this->createMock(CheckDefinitionInterface::class);

        /** @var ResultInterface $result */
        $result = $this->createMock(ResultInterface::class);

        $event = new BeforeRunCheckEvent($definition);
        $event->setResult($result);

        self::assertTrue($event->isPropagationStopped());
        self::assertEquals($result, $event->getResult());
    }
}
