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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command for get list of available groups.
 */
#[AsCommand(name: 'diagnostic:groups', description: 'List available groups.')]
class ListGroupsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'diagnostic:groups';

    /**
     * @var string
     */
    protected static $defaultDescription = 'List available groups.';

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
            ->setDescription(self::$defaultDescription);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $groups = $this->definitions->getGroups();

        if (!\count($groups)) {
            $output->writeln('<comment>No any group configured yet.</comment>');
        }

        foreach ($groups as $group) {
            $output->writeln($group);
        }

        return 0;
    }
}
