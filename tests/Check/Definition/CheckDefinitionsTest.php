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
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckDefinitionsTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreate(): void
    {
        $definitions = [
            new CheckDefinition('foo', $this->createMock(CheckInterface::class), []),
            new CheckDefinition('bar', $this->createMock(CheckInterface::class), []),
        ];

        $collection = new CheckDefinitions(...$definitions);

        self::assertEquals($definitions, \iterator_to_array($collection));
        self::assertCount(2, $definitions);
    }

    #[Test]
    public function shouldSuccessGetGroups(): void
    {
        /** @var CheckInterface $check */
        $check = $this->createMock(CheckInterface::class);

        $definitions = new CheckDefinitions(
            new CheckDefinition('check1', $check, ['foo']),
            new CheckDefinition('check2', $check, ['bar']),
            new CheckDefinition('check3', $check, []),
            new CheckDefinition('check4', $check, ['some']),
            new CheckDefinition('check5', $check, ['some'])
        );

        self::assertEquals([
            'foo',
            'bar',
            'some',
        ], $definitions->getGroups());
    }

    #[Test]
    public function shouldSuccessFilter(): void
    {
        $definitions = [
            new CheckDefinition('foo1', $this->createMock(CheckInterface::class), []),
            new CheckDefinition('foo2', $this->createMock(CheckInterface::class), []),
            new CheckDefinition('foo3', $this->createMock(CheckInterface::class), []),
            new CheckDefinition('foo4', $this->createMock(CheckInterface::class), []),
        ];

        $forTestings = [$definitions[1], $definitions[2]];

        $collection = new CheckDefinitions(...$definitions);

        $filtered = $collection->filter(function (CheckDefinition $definition) use ($forTestings) {
            return \in_array($definition, $forTestings, true);
        });

        self::assertEquals(
            new CheckDefinitions(
                $definitions[1],
                $definitions[2]
            ),
            $filtered
        );
    }

    #[Test]
    public function shouldThrowRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Duplicate definition with key "definitionKey"');

        /** @var CheckInterface $check */
        $check = $this->createMock(CheckInterface::class);

        new CheckDefinitions(
            new CheckDefinition('definitionKey', $check, []),
            new CheckDefinition('definitionKey', $check, []),
        );
    }
}
