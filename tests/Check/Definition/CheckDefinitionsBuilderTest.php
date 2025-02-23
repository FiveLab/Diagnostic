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
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionsBuilder;
use FiveLab\Component\Diagnostic\Tests\TestHelperTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckDefinitionsBuilderTest extends TestCase
{
    use TestHelperTrait;

    private CheckDefinitionsBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new CheckDefinitionsBuilder();
    }

    #[Test]
    public function shouldSuccessBuildWithoutAnyChecks(): void
    {
        $builder = new CheckDefinitionsBuilder();

        $definitions = $builder->build();

        self::assertCount(0, $definitions);
    }

    #[Test]
    public function shouldSuccessBuild(): void
    {
        $check1 = $this->createUniqueMock(CheckInterface::class);
        $check2 = $this->createUniqueMock(CheckInterface::class);
        $check3 = $this->createUniqueMock(CheckInterface::class);

        $builder = new CheckDefinitionsBuilder();

        $builder->addCheck('check1', $check1);
        $builder->addCheck('check2', $check2);
        $builder->addCheck('check3', $check3);

        self::assertEquals(new CheckDefinitions(
            new CheckDefinition('check1', $check1, []),
            new CheckDefinition('check2', $check2, []),
            new CheckDefinition('check3', $check3, [])
        ), $builder->build());
    }

    #[Test]
    public function shouldSuccessMergeGroups(): void
    {
        $check1 = $this->createUniqueMock(CheckInterface::class);
        $check2 = $this->createUniqueMock(CheckInterface::class);

        $builder = new CheckDefinitionsBuilder();

        $builder->addCheck('check1', $check1, ['foo']);
        $builder->addCheck('check1', $check1, ['bar']);
        $builder->addCheck('check2', $check2, ['some']);

        self::assertEquals(new CheckDefinitions(
            new CheckDefinition('check1', $check1, ['foo', 'bar']),
            new CheckDefinition('check2', $check2, ['some'])
        ), $builder->build());
    }

    #[Test]
    public function shouldSuccessBuildWithErrorOnFailure(): void
    {
        $check1 = $this->createUniqueMock(CheckInterface::class);
        $check2 = $this->createUniqueMock(CheckInterface::class);

        $builder = new CheckDefinitionsBuilder();

        $builder->addCheck('check1', $check1, '', true);
        $builder->addCheck('check2', $check2, '', false);

        self::assertEquals(new CheckDefinitions(
            new CheckDefinition('check1', $check1, [], true),
            new CheckDefinition('check2', $check2, [], false)
        ), $builder->build());
    }

    #[Test]
    public function shouldSuccessWithGroupAsString(): void
    {
        $check = $this->createUniqueMock(CheckInterface::class);

        $builder = new CheckDefinitionsBuilder();

        $builder->addCheck('check', $check, 'some');

        self::assertEquals(new CheckDefinitions(
            new CheckDefinition('check', $check, ['some'])
        ), $builder->build());
    }

    #[Test]
    public function shouldRemoveEmptyGroups(): void
    {
        $check1 = $this->createUniqueMock(CheckInterface::class);
        $check2 = $this->createUniqueMock(CheckInterface::class);

        $builder = new CheckDefinitionsBuilder();

        $builder->addCheck('check1', $check1, '');
        $builder->addCheck('check2', $check2, ['', 'bar', '']);

        self::assertEquals(new CheckDefinitions(
            new CheckDefinition('check1', $check1, []),
            new CheckDefinition('check2', $check2, ['bar'])
        ), $builder->build());
    }
}
