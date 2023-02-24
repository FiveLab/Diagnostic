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

namespace FiveLab\Component\Diagnostic\Tests\Check;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\LazyContainerCheck;
use FiveLab\Component\Diagnostic\Result\Success;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class LazyContainerCheckTest extends TestCase
{
    /**
     * @var CheckInterface
     */
    private CheckInterface $check;

    /**
     * @var LazyContainerCheck
     */
    private LazyContainerCheck $lazyCheck;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->check = $this->createMock(CheckInterface::class);

        $container = new Container();
        $container->set('foo.bar', $this->check);

        $this->lazyCheck = new LazyContainerCheck($container, 'foo.bar');
    }

    /**
     * @test
     */
    public function shouldSuccessCheck(): void
    {
        $this->check->expects(self::once())
            ->method('check')
            ->willReturn(new Success('Some result'));

        $result = $this->lazyCheck->check();

        self::assertEquals(new Success('Some result'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParameters(): void
    {
        $this->check->expects(self::once())
            ->method('getExtraParameters')
            ->willReturn(['foo' => 'bar', 'bar' => 'foo']);

        $result = $this->lazyCheck->getExtraParameters();

        self::assertEquals(['foo' => 'bar', 'bar' => 'foo'], $result);
    }
}
