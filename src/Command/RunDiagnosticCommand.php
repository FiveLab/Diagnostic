<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Command;

use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollection;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\CheckDefinitionsInGroupFilter;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\OrXFilter;
use FiveLab\Component\Diagnostic\Runner\RunnerInterface;
use FiveLab\Component\Diagnostic\Runner\Subscriber\ConsoleOutputDebugSubscriber;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for run diagnostic.
 */
class RunDiagnosticCommand extends Command
{
    /**
     * @var RunnerInterface
     */
    private $runner;

    /**
     * @var DefinitionCollection
     */
    private $definitions;

    /**
     * Constructor.
     *
     * @param RunnerInterface      $runner
     * @param DefinitionCollection $definitions
     */
    public function __construct(RunnerInterface $runner, DefinitionCollection $definitions)
    {
        parent::__construct();

        $this->runner = $runner;
        $this->definitions = $definitions;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('diagnostic:run')
            ->setDescription('Run diagnostics.')
            ->addOption('group', 'g', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The list of groups.', []);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (\method_exists($this->runner, 'getEventDispatcher')) {
            $this->runner->getEventDispatcher()
                ->addSubscriber(new ConsoleOutputDebugSubscriber($output));
        } else {
            $output->writeln('<comment>Cannot set console output subscriber. Runner not supported.</comment>');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
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

        return (int) !$this->runner->run($definitions);
    }
}
