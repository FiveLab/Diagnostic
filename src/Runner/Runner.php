<?php

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
    private $eventDispatcher;

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
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
            $event = new BeforeRunCheckEvent($definition);
            $this->eventDispatcher->dispatch(RunnerEvents::RUN_CHECK_BEFORE, $event);

            $result = $event->getResult();

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

            $event = new CompleteRunCheckEvent($definition, $result);
            $this->eventDispatcher->dispatch(RunnerEvents::RUN_CHECK_COMPLETE, $event);
        }

        return $allSuccess;
    }
}
