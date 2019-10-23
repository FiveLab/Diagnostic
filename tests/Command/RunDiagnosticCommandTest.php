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

namespace FiveLab\Component\Diagnostic\Tests\Command;

use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollection;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\CheckDefinitionsInGroupFilter;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\OrXFilter;
use FiveLab\Component\Diagnostic\Command\RunDiagnosticCommand;
use FiveLab\Component\Diagnostic\Runner\Runner;
use FiveLab\Component\Diagnostic\Runner\RunnerInterface;
use FiveLab\Component\Diagnostic\Runner\Subscriber\ConsoleOutputDebugSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RunDiagnosticCommandTest extends TestCase
{
    /**
     * @var RunnerInterface|MockObject
     */
    private $runner;

    /**
     * @var DefinitionCollection|MockObject
     */
    private $definitions;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var RunDiagnosticCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->runner = $this->createMock(Runner::class);
        $this->definitions = $this->createMock(DefinitionCollection::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->command = new RunDiagnosticCommand($this->runner, $this->definitions);
    }

    /**
     * @test
     */
    public function shouldSuccessConfigure(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $eventDispatcher->expects(self::once())
            ->method('addSubscriber')
            ->with(new ConsoleOutputDebugSubscriber($this->output));

        $this->runner->expects(self::once())
            ->method('getEventDispatcher')
            ->willReturn($eventDispatcher);

        $this->command->run($this->input, $this->output);

        self::assertEquals('diagnostic:run', $this->command->getName());
    }

    /**
     * @test
     */
    public function shouldSuccessRunWithSuccessStatus(): void
    {
        $this->runner->expects(self::once())
            ->method('run')
            ->with($this->definitions)
            ->willReturn(true);

        $code = $this->command->run($this->input, $this->output);

        self::assertEquals(0, $code);
    }

    /**
     * @test
     */
    public function shouldSuccessRunWithFailStatus(): void
    {
        $this->runner->expects(self::once())
            ->method('run')
            ->with($this->definitions)
            ->willReturn(false);

        $code = $this->command->run($this->input, $this->output);

        self::assertEquals(1, $code);
    }

    /**
     * @test
     */
    public function shouldSuccessRunWithGroupFiltering(): void
    {
        $this->definitions->expects(self::once())
            ->method('getGroups')
            ->willReturn(['foo', 'bar', 'some']);

        $expectedDefinitions = $this->createMock(DefinitionCollection::class);
        $expectedDefinitions->uniqueIdentifier = \uniqid();

        $this->definitions->expects(self::once())
            ->method('filter')
            ->with(new OrXFilter(
                new CheckDefinitionsInGroupFilter('foo'),
                new CheckDefinitionsInGroupFilter('bar')
            ))
            ->willReturn($expectedDefinitions);

        $this->runner->expects(self::once())
            ->method('run')
            ->with($expectedDefinitions)
            ->willReturn(true);

        $input = new ArrayInput([
            '--group' => ['foo', 'bar'],
        ]);

        $code = $this->command->run($input, $this->output);

        self::assertEquals(0, $code);
    }

    /**
     * @test
     */
    public function shouldFailInGroupNotConfigured(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The groups "some-foo", "some-bar" is not configured in your definitions.');

        $this->definitions->expects(self::once())
            ->method('getGroups')
            ->willReturn(['foo', 'bar', 'some']);


        $this->runner->expects(self::never())
            ->method('run');

        $input = new ArrayInput([
            '--group' => ['foo', 'some-foo', 'some-bar'],
        ]);

        $this->command->run($input, $this->output);
    }
}
