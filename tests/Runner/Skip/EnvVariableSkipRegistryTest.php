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

namespace FiveLab\Component\Diagnostic\Tests\Runner\Skip;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;
use FiveLab\Component\Diagnostic\Runner\Skip\EnvVariableSkipRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnvVariableSkipRegistryTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        \putenv('PHPUNIT_SKIP_HEALTH_CHECKS=');
    }

    #[Test]
    public function shouldReturnTrueIfCheckShouldBeSkipped(): void
    {
        \putenv('PHPUNIT_SKIP_HEALTH_CHECKS=foo,bar,,qwerty');

        $registry = new EnvVariableSkipRegistry('PHPUNIT_SKIP_HEALTH_CHECKS');

        $definition = $this->createDefinitionWithKey('bar');

        self::assertTrue($registry->isShouldBeSkipped($definition));
    }

    #[Test]
    public function shouldReturnFalseIfCheckShouldNotBeSkipped(): void
    {
        \putenv('PHPUNIT_SKIP_HEALTH_CHECKS=foo,bar,,qwerty');

        $registry = new EnvVariableSkipRegistry('PHPUNIT_SKIP_HEALTH_CHECKS');

        $definition = $this->createDefinitionWithKey('some');

        self::assertFalse($registry->isShouldBeSkipped($definition));
    }

    /**
     * Create the definition with key
     *
     * @param string $key
     *
     * @return CheckDefinitionInterface
     */
    private function createDefinitionWithKey(string $key): CheckDefinitionInterface
    {
        $definition = $this->createMock(CheckDefinitionInterface::class);

        $definition->expects(self::any())
            ->method('getKey')
            ->willReturn($key);

        return $definition;
    }
}
