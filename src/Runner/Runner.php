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

namespace FiveLab\Component\Diagnostic\Runner;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitions;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Runner\Event\BeforeRunCheckEvent;
use FiveLab\Component\Diagnostic\Runner\Event\CompleteRunCheckEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class Runner implements RunnerInterface
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(?EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher ?: new EventDispatcher();
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function run(CheckDefinitions $definitions): bool
    {
        $allSuccess = true;

        foreach ($definitions as $definition) {
            $beforeRunEvent = new BeforeRunCheckEvent($definition);
            /** @var BeforeRunCheckEvent $beforeRunEvent */
            $beforeRunEvent = $this->eventDispatcher->dispatch($beforeRunEvent, RunnerEvents::RUN_CHECK_BEFORE);

            $result = $beforeRunEvent->getResult();

            if (!$result) {
                try {
                    $result = $definition->check->check();
                } catch (\Throwable $e) {
                    $result = new Failure(\sprintf(
                        'Catch exception (%s): %s',
                        \get_class($e),
                        $e->getMessage()
                    ), $e);
                }
            }

            if ($result instanceof Failure && $definition->errorOnFailure) {
                $allSuccess = false;
            }

            $afterRunEvent = new CompleteRunCheckEvent($definition, $result);
            $this->eventDispatcher->dispatch($afterRunEvent, RunnerEvents::RUN_CHECK_COMPLETE);
        }

        return $allSuccess;
    }
}
