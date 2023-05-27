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

namespace FiveLab\Component\Diagnostic\Command;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command for get list of available checks.
 */
#[AsCommand(name: 'diagnostic:checks', description: 'List available checks.')]
class ListChecksCommand extends Command
{
    use FilterDefinitionsTrait;

    /**
     * @var string
     */
    protected static $defaultName = 'diagnostic:checks';

    /**
     * @var string
     */
    protected static $defaultDescription = 'List available checks.';

    /**
     * Constructor.
     *
     * @param CheckDefinitions $definitions
     */
    public function __construct(private readonly CheckDefinitions $definitions)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addOption('group', 'g', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The list of groups.', []);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $definitions = clone $this->definitions;

        if ($input->getOption('group')) {
            $definitions = $this->filterDefinitionsByGroupInInput($definitions, (array) $input->getOption('group'));
        }

        foreach ($definitions as $definition) {
            $output->write($definition->key);

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->write(': '.\get_class($definition));
            }

            $output->writeln('');
        }

        return 0;
    }
}
