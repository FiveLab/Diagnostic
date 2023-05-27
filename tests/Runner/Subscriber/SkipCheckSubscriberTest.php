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

namespace FiveLab\Component\Diagnostic\Tests\Runner\Subscriber;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinition;
use FiveLab\Component\Diagnostic\Result\Skip;
use FiveLab\Component\Diagnostic\Runner\Event\BeforeRunCheckEvent;
use FiveLab\Component\Diagnostic\Runner\Skip\SkipRegistryInterface;
use FiveLab\Component\Diagnostic\Runner\Subscriber\SkipCheckSubscriber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SkipCheckSubscriberTest extends TestCase
{
    /**
     * @var SkipRegistryInterface|MockObject
     */
    private SkipRegistryInterface $skipRegistry;

    /**
     * @var SkipCheckSubscriber
     */
    private SkipCheckSubscriber $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->skipRegistry = $this->createMock(SkipRegistryInterface::class);
        $this->subscriber = new SkipCheckSubscriber($this->skipRegistry);
    }

    #[Test]
    public function shouldReturnCorrectListenEvents(): void
    {
        $listenEvents = $this->subscriber::getSubscribedEvents();

        self::assertEquals([
            'check.run.before' => 'onBeforeRunCheck',
        ], $listenEvents);
    }

    #[Test]
    public function shouldSetSkipResultIfShouldBeSkipped(): void
    {
        $definition = new CheckDefinition('', $this->createMock(CheckInterface::class), []);

        $this->skipRegistry->expects(self::once())
            ->method('isShouldBeSkipped')
            ->willReturn(true);

        $event = new BeforeRunCheckEvent($definition);

        $this->subscriber->onBeforeRunCheck($event);

        self::assertEquals(new Skip('Must be skipped.'), $event->getResult());
    }

    #[Test]
    public function shouldNotSetResultIfShouldNotBeSkipped(): void
    {
        $definition = new CheckDefinition('', $this->createMock(CheckInterface::class), []);

        $this->skipRegistry->expects(self::once())
            ->method('isShouldBeSkipped')
            ->willReturn(false);

        $event = new BeforeRunCheckEvent($definition);

        $this->subscriber->onBeforeRunCheck($event);

        self::assertNull($event->getResult());
    }
}
