<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Runner\Subscriber;

use FiveLab\Component\Diagnostic\Result\Skip;
use FiveLab\Component\Diagnostic\Runner\Event\BeforeRunCheckEvent;
use FiveLab\Component\Diagnostic\Runner\RunnerEvents;
use FiveLab\Component\Diagnostic\Runner\Skip\SkipRegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The subscriber for skip checks.
 */
class SkipCheckSubscriber implements EventSubscriberInterface
{
    /**
     * @var SkipRegistryInterface
     */
    private $skipRegistry;

    /**
     * Constructor.
     *
     * @param SkipRegistryInterface $skipRegistry
     */
    public function __construct(SkipRegistryInterface $skipRegistry)
    {
        $this->skipRegistry = $skipRegistry;
    }

    /**
     * Call before run check.
     *
     * @param BeforeRunCheckEvent $event
     */
    public function onBeforeRunCheck(BeforeRunCheckEvent $event): void
    {
        if ($this->skipRegistry->isShouldBeSkipped($event->getDefinition())) {
            $event->setResult(new Skip('Must be skipped.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            RunnerEvents::RUN_CHECK_BEFORE => 'onBeforeRunCheck',
        ];
    }
}
