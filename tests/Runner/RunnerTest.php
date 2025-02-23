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
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinition;
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitions;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Skip;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Runner\Event\BeforeRunCheckEvent;
use FiveLab\Component\Diagnostic\Runner\Event\CompleteRunCheckEvent;
use FiveLab\Component\Diagnostic\Runner\Runner;
use FiveLab\Component\Diagnostic\Runner\RunnerEvents;
use FiveLab\Component\Diagnostic\Tests\TestHelperTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RunnerTest extends TestCase
{
    use TestHelperTrait;

    private EventDispatcherInterface $eventDispatcher;
    private Runner $runner;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->runner = new Runner($this->eventDispatcher);
    }

    #[Test]
    public function shouldSuccessCreateWithoutEventDispatcher(): void
    {
        $runner = new Runner();

        self::assertEquals(new EventDispatcher(), $runner->getEventDispatcher());
    }

    #[Test]
    public function shouldSuccessCreateWithEventDispatcher(): void
    {
        $eventDispatcher = new EventDispatcher();
        $runner = new Runner($eventDispatcher);

        self::assertEquals($eventDispatcher, $runner->getEventDispatcher());
        self::assertEquals(\spl_object_hash($eventDispatcher), \spl_object_hash($runner->getEventDispatcher()));
    }

    #[Test]
    public function shouldSuccessRun(): void
    {
        $result = new Success('foo');

        $definition1 = $this->createDefinitionWithResult($result);
        $definition2 = $this->createDefinitionWithResult($result);

        $matcher = self::exactly(4);

        $map = [
            [new BeforeRunCheckEvent($definition1), RunnerEvents::RUN_CHECK_BEFORE],
            [new CompleteRunCheckEvent($definition1, $result), RunnerEvents::RUN_CHECK_COMPLETE],
            [new BeforeRunCheckEvent($definition2), RunnerEvents::RUN_CHECK_BEFORE],
            [new CompleteRunCheckEvent($definition2, $result), RunnerEvents::RUN_CHECK_COMPLETE],
        ];

        $this->eventDispatcher->expects($matcher)
            ->method('dispatch')
            ->with(
                self::callback($this->createConsecutiveCallback($matcher, $map, 0)),
                self::callback($this->createConsecutiveCallback($matcher, $map, 1))
            )
            ->willReturnCallback(static fn(mixed $e) => $e);

        $result = $this->runner->run(new CheckDefinitions($definition1, $definition2));

        self::assertTrue($result);
    }

    #[Test]
    public function shouldFailRun(): void
    {
        $success = new Success('foo');
        $fail = new Failure('bar');

        $definition1 = $this->createDefinitionWithResult($success);
        $definition2 = $this->createDefinitionWithResult($fail);
        $definition3 = $this->createDefinitionWithResult($success);

        $map = [
            [new BeforeRunCheckEvent($definition1), RunnerEvents::RUN_CHECK_BEFORE],
            [new CompleteRunCheckEvent($definition1, $success), RunnerEvents::RUN_CHECK_COMPLETE],
            [new BeforeRunCheckEvent($definition2), RunnerEvents::RUN_CHECK_BEFORE],
            [new CompleteRunCheckEvent($definition2, $fail), RunnerEvents::RUN_CHECK_COMPLETE],
            [new BeforeRunCheckEvent($definition3), RunnerEvents::RUN_CHECK_BEFORE],
            [new CompleteRunCheckEvent($definition3, $success), RunnerEvents::RUN_CHECK_COMPLETE],
        ];

        $matcher = self::exactly(6);

        $this->eventDispatcher->expects($matcher)
            ->method('dispatch')
            ->with(
                self::callback($this->createConsecutiveCallback($matcher, $map, 0)),
                self::callback($this->createConsecutiveCallback($matcher, $map, 1))
            )
            ->willReturnCallback(static fn(mixed $e) => $e);

        $result = $this->runner->run(new CheckDefinitions($definition1, $definition2, $definition3));

        self::assertFalse($result);
    }

    #[Test]
    public function shouldNotRunCheckIfEventContainResult(): void
    {
        $definition1 = $this->createDefinitionWithResult();
        $definition2 = $this->createDefinitionWithResult(new Success('some'));

        $def1 = new BeforeRunCheckEvent($definition1);
        $def1->setResult(new Skip('skipped'));

        $map = [
            [new BeforeRunCheckEvent($definition1), RunnerEvents::RUN_CHECK_BEFORE],
            [new CompleteRunCheckEvent($definition1, new Skip('skipped')), RunnerEvents::RUN_CHECK_COMPLETE],
            [new BeforeRunCheckEvent($definition2), RunnerEvents::RUN_CHECK_BEFORE],
            [new CompleteRunCheckEvent($definition2, new Success('some')), RunnerEvents::RUN_CHECK_COMPLETE],
        ];

        $matcher = self::exactly(4);

        $this->eventDispatcher->expects($matcher)
            ->method('dispatch')
            ->with(
                self::callback($this->createConsecutiveCallback($matcher, $map, 0)),
                self::callback($this->createConsecutiveCallback($matcher, $map, 1))
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(static function (BeforeRunCheckEvent $event) {
                    $event = clone $event;
                    $event->setResult(new Skip('skipped'));

                    return $event;
                }),
                new CompleteRunCheckEvent($definition1, new Skip('skipped')),
                new BeforeRunCheckEvent($definition2),
                new CompleteRunCheckEvent($definition2, new Success('some'))
            );

        $result = $this->runner->run(new CheckDefinitions($definition1, $definition2));

        self::assertTrue($result);
    }

    #[Test]
    public function shouldCorrectCatchExceptionInCheck(): void
    {
        $check = $this->createMock(CheckInterface::class);
        $error = new \RuntimeException('some-foo-bar');

        $check->expects(self::once())
            ->method('check')
            ->willThrowException($error);

        $definition = new CheckDefinition('', $check, []);

        $map = [
            [new BeforeRunCheckEvent($definition), RunnerEvents::RUN_CHECK_BEFORE],
            [new CompleteRunCheckEvent($definition, new Failure('Catch exception (RuntimeException): some-foo-bar', $error)), RunnerEvents::RUN_CHECK_COMPLETE],
        ];

        $matcher = self::exactly(2);

        $this->eventDispatcher->expects($matcher)
            ->method('dispatch')
            ->with(
                self::callback($this->createConsecutiveCallback($matcher, $map, 0)),
                self::callback($this->createConsecutiveCallback($matcher, $map, 1))
            )
            ->willReturnCallback(static fn(mixed $e) => $e);

        $result = $this->runner->run(new CheckDefinitions($definition));

        self::assertFalse($result);
    }

    #[Test]
    public function shouldSuccessIgnoreErrorOnFailure(): void
    {
        $definition1 = $this->createDefinitionWithResult(new Failure('bla bla'), false);

        $this->eventDispatcher->expects(self::any())
            ->method('dispatch')
            ->willReturnArgument(0);

        $result = $this->runner->run(new CheckDefinitions($definition1));

        self::assertTrue($result);
    }

    #[Test]
    public function shouldNotIgnoreErrorOnFailure(): void
    {
        $definition1 = $this->createDefinitionWithResult(new Failure('bla bla'), true);

        $this->eventDispatcher->expects(self::any())
            ->method('dispatch')
            ->willReturnArgument(0);

        $result = $this->runner->run(new CheckDefinitions($definition1));

        self::assertFalse($result);
    }

    private function createDefinitionWithResult(?Result $result = null, bool $errorOnFailure = true): CheckDefinition
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

        return new CheckDefinition('', $check, [], $errorOnFailure);
    }
}
