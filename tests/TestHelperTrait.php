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

namespace FiveLab\Component\Diagnostic\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use SebastianBergmann\Comparator\Factory;

trait TestHelperTrait
{
    /**
     * @template T
     *
     * @param class-string<T> $className
     *
     * @return T|MockObject
     */
    protected function createUniqueMock(string $className): object
    {
        static $creations = 0;

        $creations++;

        $classNameParts = \explode('\\', $className);
        $classNameLastPart = \array_pop($classNameParts);

        $mockClassName = \sprintf('Mock_%s_%d', $classNameLastPart, $creations);

        return $this->getMockBuilder($className)
            ->setMockClassName($mockClassName)
            ->getMock();
    }

    /**
     * Create callback for consecutive calls
     *
     * @param InvokedCount                  $matcher
     * @param array<int, array<int, mixed>> $arguments
     * @param int                           $index
     *
     * @return \Closure
     */
    public function createConsecutiveCallback(InvokedCount $matcher, array $arguments, int $index = 0): \Closure
    {
        return static function (mixed $arg) use ($matcher, $arguments, $index): bool {
            $expectedArguments = $arguments[$matcher->numberOfInvocations() - 1];
            $expectedArgument = $expectedArguments[$index];

            $comparator = Factory::getInstance()->getComparatorFor($expectedArgument, $arg);

            $comparator->assertEquals($expectedArgument, $arg);

            return true;
        };
    }
}
