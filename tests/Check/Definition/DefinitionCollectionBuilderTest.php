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
use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollection;
use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollectionBuilder;
use PHPUnit\Framework\TestCase;

class DefinitionCollectionBuilderTest extends TestCase
{
    /**
     * @var DefinitionCollectionBuilder
     */
    private DefinitionCollectionBuilder $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->builder = new DefinitionCollectionBuilder();
    }

    /**
     * @test
     */
    public function shouldSuccessBuildWithoutAnyChecks(): void
    {
        $builder = new DefinitionCollectionBuilder();

        $definitions = $builder->build();

        self::assertCount(0, $definitions);
    }

    /**
     * @test
     */
    public function shouldSuccessBuild(): void
    {
        $check1 = $this->createUniqueCheck();
        $check2 = $this->createUniqueCheck();
        $check3 = $this->createUniqueCheck();

        $builder = new DefinitionCollectionBuilder();

        $builder->addCheck('check1', $check1);
        $builder->addCheck('check2', $check2);
        $builder->addCheck('check3', $check3);

        self::assertEquals(new DefinitionCollection(
            new CheckDefinition('check1', $check1, []),
            new CheckDefinition('check2', $check2, []),
            new CheckDefinition('check3', $check3, [])
        ), $builder->build());
    }

    /**
     * @test
     */
    public function shouldSuccessMergeGroups(): void
    {
        $check1 = $this->createUniqueCheck();
        $check2 = $this->createUniqueCheck();

        $builder = new DefinitionCollectionBuilder();

        $builder->addCheck('check1', $check1, ['foo']);
        $builder->addCheck('check1', $check1, ['bar']);
        $builder->addCheck('check2', $check2, ['some']);

        self::assertEquals(new DefinitionCollection(
            new CheckDefinition('check1', $check1, ['foo', 'bar']),
            new CheckDefinition('check2', $check2, ['some'])
        ), $builder->build());
    }

    /**
     * @test
     */
    public function shouldSuccessWithGroupAsString(): void
    {
        $check = $this->createUniqueCheck();

        $builder = new DefinitionCollectionBuilder();

        $builder->addCheck('check', $check, 'some');

        self::assertEquals(new DefinitionCollection(
            new CheckDefinition('check', $check, ['some'])
        ), $builder->build());
    }

    /**
     * @test
     */
    public function shouldRemoveEmptyGroups(): void
    {
        $check1 = $this->createUniqueCheck();
        $check2 = $this->createUniqueCheck();

        $builder = new DefinitionCollectionBuilder();

        $builder->addCheck('check1', $check1, '');
        $builder->addCheck('check2', $check2, ['', 'bar', '']);

        self::assertEquals(new DefinitionCollection(
            new CheckDefinition('check1', $check1, []),
            new CheckDefinition('check2', $check2, ['bar'])
        ), $builder->build());
    }

    /**
     * Create unique check
     *
     * @return CheckInterface
     */
    private function createUniqueCheck(): CheckInterface
    {
        $check = $this->createMock(CheckInterface::class);

        $check->uniqueIdentifier = \uniqid((string) \random_int(0, PHP_INT_MAX), true);

        return $check;
    }
}
