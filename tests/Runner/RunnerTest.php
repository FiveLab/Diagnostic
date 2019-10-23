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

namespace FiveLab\Component\Diagnostic\Tests\Runner;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;
use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollection;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Skip;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Runner\Event\BeforeRunCheckEvent;
use FiveLab\Component\Diagnostic\Runner\Event\CompleteRunCheckEvent;
use FiveLab\Component\Diagnostic\Runner\Runner;
use FiveLab\Component\Diagnostic\Runner\RunnerEvents;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RunnerTest extends TestCase
{
    /**
     * @var EventDispatcherInterface|MockObject
     */
    private $eventDispatcher;

    /**
     * @var Runner
     */
    private $runner;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->runner = new Runner($this->eventDispatcher);
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithoutEventDispatcher(): void
    {
        $runner = new Runner();

        self::assertEquals(new EventDispatcher(), $runner->getEventDispatcher());
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithEventDispatcher(): void
    {
        $eventDispatcher = new EventDispatcher();
        $runner = new Runner($eventDispatcher);

        self::assertEquals($eventDispatcher, $runner->getEventDispatcher());
        self::assertEquals(\spl_object_hash($eventDispatcher), \spl_object_hash($runner->getEventDispatcher()));
    }

    /**
     * @test
     */
    public function shouldSuccessRun(): void
    {
        $result = new Success('foo');

        $definition1 = $this->createDefinitionWithResult($result);
        $definition2 = $this->createDefinitionWithResult($result);

        $this->expectDispatchForDefinition(0, $definition1, $result);
        $this->expectDispatchForDefinition(1, $definition2, $result);

        $result = $this->runner->run(new DefinitionCollection($definition1, $definition2));

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function shouldFailRun(): void
    {
        $success = new Success('foo');
        $fail = new Failure('bar');

        $definition1 = $this->createDefinitionWithResult($success);
        $definition2 = $this->createDefinitionWithResult($fail);
        $definition3 = $this->createDefinitionWithResult($success);

        $this->expectDispatchForDefinition(0, $definition1, $success);
        $this->expectDispatchForDefinition(1, $definition2, $fail);
        $this->expectDispatchForDefinition(2, $definition3, $success);

        $result = $this->runner->run(new DefinitionCollection($definition1, $definition2, $definition3));

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function shouldNotRunCheckIfEventContainResult(): void
    {
        $definition1 = $this->createDefinitionWithResult();
        $definition2 = $this->createDefinitionWithResult(new Success('some'));

        $this->eventDispatcher->expects(self::at(0))
            ->method('dispatch')
            ->with(RunnerEvents::RUN_CHECK_BEFORE, new BeforeRunCheckEvent($definition1))
            ->willReturnCallback(function (string $eventName, BeforeRunCheckEvent $event) {
                $event->setResult(new Skip('skipped'));
            });

        $this->eventDispatcher->expects(self::at(1))
            ->method('dispatch')
            ->with(RunnerEvents::RUN_CHECK_COMPLETE, new CompleteRunCheckEvent($definition1, new Skip('skipped')));

        $this->expectDispatchForDefinition(1, $definition2, new Success('some'));

        $result = $this->runner->run(new DefinitionCollection($definition1, $definition2));

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function shouldCorrectCatchExceptionInCheck(): void
    {
        $check = $this->createMock(CheckInterface::class);

        $check->expects(self::once())
            ->method('check')
            ->willThrowException(new \RuntimeException('some-foo-bar'));

        $definition = $this->createMock(CheckDefinitionInterface::class);

        $definition->expects(self::any())
            ->method('getCheck')
            ->willReturn($check);

        $this->expectDispatchForDefinition(0, $definition, new Failure('Catch exception (RuntimeException): some-foo-bar'));

        $result = $this->runner->run(new DefinitionCollection($definition));

        self::assertFalse($result);
    }

    /**
     * Expect dispatch for definition
     *
     * @param int                      $index
     * @param CheckDefinitionInterface $definition
     * @param ResultInterface          $result
     */
    private function expectDispatchForDefinition($index, CheckDefinitionInterface $definition, ResultInterface $result): void
    {
        $index = $index * 2;

        $this->eventDispatcher->expects(self::at($index))
            ->method('dispatch')
            ->with(RunnerEvents::RUN_CHECK_BEFORE, new BeforeRunCheckEvent($definition));

        $this->eventDispatcher->expects(self::at($index + 1))
            ->method('dispatch')
            ->with(RunnerEvents::RUN_CHECK_COMPLETE, new CompleteRunCheckEvent($definition, $result));
    }

    /**
     * Create definition
     *
     * @param ResultInterface $result
     *
     * @return CheckDefinitionInterface
     */
    private function createDefinitionWithResult(ResultInterface $result = null): CheckDefinitionInterface
    {
        $check = $this->createMock(CheckInterface::class);

        if ($result) {
            $check->expects(self::once())
                ->method('check')
                ->willReturn($result);
        } else {
            $check->expects(self::never())
                ->method('check');
        }

        $definition = $this->createMock(CheckDefinitionInterface::class);

        $definition->expects(self::any())
            ->method('getCheck')
            ->willReturn($check);

        return $definition;
    }
}
