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
use FiveLab\Component\Diagnostic\Command\ListGroupsCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ListGroupsCommandTest extends TestCase
{
    /**
     * @var ArrayInput
     */
    private ArrayInput $input;

    /**
     * @var BufferedOutput
     */
    private BufferedOutput $output;

    /**
     * @var DefinitionCollection|MockObject
     */
    private DefinitionCollection $definitions;

    /**
     * @var ListGroupsCommand
     */
    private ListGroupsCommand $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->input = new ArrayInput([]);
        $this->output = new BufferedOutput();
        $this->definitions = $this->createMock(DefinitionCollection::class);
        $this->command = new ListGroupsCommand($this->definitions);
    }

    #[Test]
    public function shouldSuccessConfigure(): void
    {
        self::assertEquals('diagnostic:groups', $this->command->getName());
    }

    #[Test]
    public function shouldSuccessRunIfGroupsNotConfigured(): void
    {
        $this->command->run($this->input, $this->output);

        $expectedOutput = <<<OUTPUT
No any group configured yet.

OUTPUT;

        self::assertEquals($expectedOutput, $this->output->fetch());
    }

    #[Test]
    public function shouldSuccessRun(): void
    {
        $this->definitions->expects(self::once())
            ->method('getGroups')
            ->willReturn(['foo', 'bar']);

        $this->command->run($this->input, $this->output);

        $expectedOutput = <<<OUTPUT
foo
bar

OUTPUT;

        self::assertEquals($expectedOutput, $this->output->fetch());
    }
}
