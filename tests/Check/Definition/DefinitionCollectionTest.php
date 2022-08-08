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
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;
use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollection;
use PHPUnit\Framework\TestCase;

class DefinitionCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $definitions = [
            $this->createMock(CheckDefinitionInterface::class),
            $this->createMock(CheckDefinitionInterface::class),
        ];

        $collection = new DefinitionCollection(...$definitions);

        self::assertEquals($definitions, \iterator_to_array($collection));
        self::assertCount(2, $definitions);
    }

    /**
     * @test
     */
    public function shouldSuccessGetGroups(): void
    {
        /** @var CheckInterface $check */
        $check = $this->createMock(CheckInterface::class);

        $definitions = new DefinitionCollection(
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

    /**
     * @test
     */
    public function shouldSuccessFilter(): void
    {
        $definitions = [
            $this->createMock(CheckDefinitionInterface::class),
            $this->createMock(CheckDefinitionInterface::class),
            $this->createMock(CheckDefinitionInterface::class),
            $this->createMock(CheckDefinitionInterface::class),
        ];

        $definitions[1]->__forTesting = true;
        $definitions[2]->__forTesting = true;

        $collection = new DefinitionCollection(...$definitions);

        $filtered = $collection->filter(function (CheckDefinitionInterface $definition) {
            return \property_exists($definition, '__forTesting');
        });

        self::assertEquals(
            new DefinitionCollection(
                $definitions[1],
                $definitions[2]
            ),
            $filtered
        );
    }

    /**
     * @test
     */
    public function shouldThrowRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Duplicate definition with key "definitionKey"');

        /** @var CheckInterface $check */
        $check = $this->createMock(CheckInterface::class);

        new DefinitionCollection(
            new CheckDefinition('definitionKey', $check, []),
            new CheckDefinition('definitionKey', $check, []),
        );
    }
}
