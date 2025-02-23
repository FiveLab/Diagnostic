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

namespace FiveLab\Component\Diagnostic\Tests\Check\Definition;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckDefinitionTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreate(): void
    {
        $check = $this->createMock(CheckInterface::class);

        $definition = new CheckDefinition('some', $check, ['foo', 'bar']);

        self::assertEquals('some', $definition->key);
        self::assertEquals($check, $definition->check);
        self::assertEquals(['foo', 'bar'], $definition->groups);
        self::assertTrue($definition->errorOnFailure);
    }
}
