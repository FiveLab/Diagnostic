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

use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command for get list of available checks.
 */
class ListChecksCommand extends Command
{
    use FilterDefinitionsTrait;

    /**
     * @var DefinitionCollection
     */
    private DefinitionCollection $definitions;

    /**
     * Constructor.
     *
     * @param DefinitionCollection $definitions
     */
    public function __construct(DefinitionCollection $definitions)
    {
        parent::__construct();

        $this->definitions = $definitions;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('diagnostic:checks')
            ->setDescription('List available checks.')
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
            $output->write($definition->getKey());

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->write(': '.\get_class($definition));
            }

            $output->writeln('');
        }

        return 0;
    }
}
