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

use FiveLab\Component\Diagnostic\Result\Skip;
use FiveLab\Component\Diagnostic\Runner\Event\BeforeRunCheckEvent;
use FiveLab\Component\Diagnostic\Runner\RunnerEvents;
use FiveLab\Component\Diagnostic\Runner\Skip\SkipRegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class SkipCheckSubscriber implements EventSubscriberInterface
{
    public function __construct(private SkipRegistryInterface $skipRegistry)
    {
    }

    public function onBeforeRunCheck(BeforeRunCheckEvent $event): BeforeRunCheckEvent
    {
        if ($this->skipRegistry->isShouldBeSkipped($event->definition)) {
            $event->setResult(new Skip('Must be skipped.'));
        }

        return $event;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RunnerEvents::RUN_CHECK_BEFORE => 'onBeforeRunCheck',
        ];
    }
}
