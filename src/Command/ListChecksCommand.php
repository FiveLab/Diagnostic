<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Command;

use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollection;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\CheckDefinitionsInGroupFilter;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\OrXFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command for get list of available checks.
 */
class ListChecksCommand extends Command
{
    /**
     * @var DefinitionCollection
     */
    private $definitions;

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
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $definitions = clone $this->definitions;

        if ($input->getOption('group')) {
            $groups = $input->getOption('group');

            $notExistenceGroups = \array_diff($groups, $definitions->getGroups());

            if (\count($notExistenceGroups)) {
                throw new \InvalidArgumentException(\sprintf(
                    'The groups "%s" is not configured in your definitions.',
                    \implode('", "', $notExistenceGroups)
                ));
            }

            $filters = \array_map(function (string $group) {
                return new CheckDefinitionsInGroupFilter($group);
            }, $input->getOption('group'));

            $filter = new OrXFilter(...$filters);

            $definitions = $definitions->filter($filter);
        }

        foreach ($definitions as $definition) {
            $output->write($definition->getKey());

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->write(': '.\get_class($definition));
            }

            $output->writeln('');
        }
    }
}
