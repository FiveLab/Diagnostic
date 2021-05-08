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

use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollection;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Runner\Event\BeforeRunCheckEvent;
use FiveLab\Component\Diagnostic\Runner\Event\CompleteRunCheckEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Default runner.
 */
class Runner implements RunnerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $eventDispatcher;

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher ?: new EventDispatcher();
    }

    /**
     * Get the event dispatcher.
     * Many plugins can add listeners or subscribers in runtime process.
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function run(DefinitionCollection $definitions): bool
    {
        $allSuccess = true;

        foreach ($definitions as $definition) {
            $beforeRunEvent = new BeforeRunCheckEvent($definition);
            /** @var BeforeRunCheckEvent $beforeRunEvent */
            $beforeRunEvent = $this->eventDispatcher->dispatch($beforeRunEvent, RunnerEvents::RUN_CHECK_BEFORE);

            $result = $beforeRunEvent->getResult();

            if (!$result) {
                try {
                    $result = $definition->getCheck()->check();
                } catch (\Throwable $e) {
                    $result = new Failure(\sprintf(
                        'Catch exception (%s): %s',
                        \get_class($e),
                        $e->getMessage()
                    ));
                }
            }

            if ($result instanceof Failure) {
                $allSuccess = false;
            }

            $afterRunEvent = new CompleteRunCheckEvent($definition, $result);
            $this->eventDispatcher->dispatch($afterRunEvent, RunnerEvents::RUN_CHECK_COMPLETE);
        }

        return $allSuccess;
    }
}
