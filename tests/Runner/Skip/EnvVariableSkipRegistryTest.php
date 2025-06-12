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

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinition;
use FiveLab\Component\Diagnostic\Runner\Skip\EnvVariableSkipRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnvVariableSkipRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        \putenv('PHPUNIT_SKIP_HEALTH_CHECKS=');
        \putenv('PHPUNIT_SKIP_HEALTH_GROUPS=');
    }

    #[Test]
    public function shouldSuccessWorkWithoutVariables(): void
    {
        $registry = new EnvVariableSkipRegistry();

        $definition = $this->createDefinitionWithKeyAndGroup('foo', []);

        self::assertFalse($registry->isShouldBeSkipped($definition));
    }

    #[Test]
    public function shouldReturnTrueIfCheckShouldBeSkipped(): void
    {
        \putenv('PHPUNIT_SKIP_HEALTH_CHECKS=foo,bar,,qwerty');

        $registry = new EnvVariableSkipRegistry('PHPUNIT_SKIP_HEALTH_CHECKS');

        $definition = $this->createDefinitionWithKeyAndGroup('bar', []);

        self::assertTrue($registry->isShouldBeSkipped($definition));
    }

    #[Test]
    public function shouldReturnFalseIfCheckShouldNotBeSkipped(): void
    {
        \putenv('PHPUNIT_SKIP_HEALTH_CHECKS=foo,bar,,qwerty');

        $registry = new EnvVariableSkipRegistry('PHPUNIT_SKIP_HEALTH_CHECKS');

        $definition = $this->createDefinitionWithKeyAndGroup('some', []);

        self::assertFalse($registry->isShouldBeSkipped($definition));
    }

    #[Test]
    public function shouldReturnTrueIfCheckShouldBeSkippedByGroups(): void
    {
        \putenv('PHPUNIT_SKIP_HEALTH_GROUPS=group1,group2,,,group3');

        $registry = new EnvVariableSkipRegistry('PHPUNIT_SKIP_HEALTH_CHECKS', 'PHPUNIT_SKIP_HEALTH_GROUPS');

        $definition = $this->createDefinitionWithKeyAndGroup('some', ['group2', 'group4']);

        self::assertTrue($registry->isShouldBeSkipped($definition));
    }

    private function createDefinitionWithKeyAndGroup(string $key, array $groups): CheckDefinition
    {
        return new CheckDefinition($key, $this->createMock(CheckInterface::class), $groups);
    }
}
