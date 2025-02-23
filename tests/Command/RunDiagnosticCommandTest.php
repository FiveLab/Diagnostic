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

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitions;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\CheckDefinitionsInGroupFilter;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\OrXFilter;
use FiveLab\Component\Diagnostic\Command\RunDiagnosticCommand;
use FiveLab\Component\Diagnostic\Runner\RunnerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunDiagnosticCommandTest extends TestCase
{
    private RunnerInterface $runner;
    private CheckDefinitions $definitions;
    private InputInterface $input;
    private OutputInterface $output;
    private RunDiagnosticCommand $command;

    protected function setUp(): void
    {
        $this->runner = $this->createMock(RunnerInterface::class);
        $this->definitions = $this->createMock(CheckDefinitions::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->command = new RunDiagnosticCommand($this->runner, $this->definitions);
    }

    #[Test]
    public function shouldSuccessConfigure(): void
    {
        $this->command->run($this->input, $this->output);

        self::assertEquals('diagnostic:run', $this->command->getName());
    }

    #[Test]
    public function shouldSuccessRunWithSuccessStatus(): void
    {
        $this->runner->expects(self::once())
            ->method('run')
            ->with($this->definitions)
            ->willReturn(true);

        $code = $this->command->run($this->input, $this->output);

        self::assertEquals(0, $code);
    }

    #[Test]
    public function shouldSuccessRunWithFailStatus(): void
    {
        $this->runner->expects(self::once())
            ->method('run')
            ->with($this->definitions)
            ->willReturn(false);

        $code = $this->command->run($this->input, $this->output);

        self::assertEquals(1, $code);
    }

    #[Test]
    public function shouldSuccessRunWithGroupFiltering(): void
    {
        $this->definitions->expects(self::once())
            ->method('getGroups')
            ->willReturn(['foo', 'bar', 'some']);

        $expectedDefinitions = $this->createMock(CheckDefinitions::class);

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

    #[Test]
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
