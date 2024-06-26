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

namespace FiveLab\Component\Diagnostic\Runner\Subscriber;

use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Skip;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Result\Warning;
use FiveLab\Component\Diagnostic\Runner\Event\CompleteRunCheckEvent;
use FiveLab\Component\Diagnostic\Runner\RunnerEvents;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The subscriber for render info about run in console.
 */
readonly class ConsoleOutputDebugSubscriber implements EventSubscriberInterface
{
    /**
     * Constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(private OutputInterface $output)
    {
    }

    /**
     * On check complete
     *
     * @param CompleteRunCheckEvent $event
     *
     * @return CompleteRunCheckEvent
     */
    public function onCheckComplete(CompleteRunCheckEvent $event): CompleteRunCheckEvent
    {
        $definition = $event->definition;
        $result = $event->result;

        $statusVerbosity = OutputInterface::VERBOSITY_VERBOSE;
        $paramsVerbosity = OutputInterface::VERBOSITY_DEBUG;

        if ($result instanceof Failure) {
            $statusText = \sprintf('<error>FAIL%s</error>', $definition->errorOnFailure ? '' : ' (ignore)');
            $statusVerbosity = OutputInterface::VERBOSITY_NORMAL;
            $paramsVerbosity = OutputInterface::VERBOSITY_NORMAL;
        } elseif ($result instanceof Warning) {
            $statusText = '<comment>WARN</comment>';
            $statusVerbosity = OutputInterface::VERBOSITY_VERY_VERBOSE;
            $paramsVerbosity = OutputInterface::VERBOSITY_VERY_VERBOSE;
        } elseif ($result instanceof Skip) {
            $statusText = '<question>SKIP</question>';
        } elseif ($result instanceof Success) {
            $statusText = '<info>OK  </info>';
        } else {
            throw new \InvalidArgumentException(\sprintf(
                'Undefined result with class "%s".',
                \get_class($result)
            ));
        }

        if ($this->output->getVerbosity() >= $statusVerbosity) {
            $this->output->writeln(\sprintf(
                '%s %s: %s',
                $statusText,
                $definition->key,
                $result->message
            ));
        }

        if ($this->output->getVerbosity() >= $paramsVerbosity) {
            $this->writeAdditionalParameters($definition->check->getExtraParameters());
            $this->output->writeln('');
        }

        return $event;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RunnerEvents::RUN_CHECK_COMPLETE => ['onCheckComplete', 128],
        ];
    }

    /**
     * Write additional parameters
     *
     * @param array<string, mixed> $params
     * @param int                  $leftPad
     */
    private function writeAdditionalParameters(array $params, int $leftPad = 1): void
    {
        foreach ($params as $name => $value) {
            if (\is_array($value)) {
                $line = \sprintf('%s%s:', \str_repeat(' ', $leftPad * 2), $name);

                $this->output->writeln($line);

                $this->writeAdditionalParameters($value, $leftPad + 1);

                continue;
            }

            $line = \sprintf('%s%s: %s', \str_repeat(' ', $leftPad * 2), $name, $value);

            $this->output->writeln($line);
        }
    }
}
