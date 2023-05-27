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

namespace FiveLab\Component\Diagnostic\Tests\Check\Definition\Filter;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinition;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\OrXFilter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OrXFilterTest extends TestCase
{
    #[Test]
    public function shouldReturnFalseWithoutAnyConditions(): void
    {
        $filter = new OrXFilter();

        $result = $filter->__invoke($this->createDefinition());

        self::assertFalse($result);
    }

    #[Test]
    public function shouldReturnTrueIfOneFromClosuresReturnTrue(): void
    {
        $filter = new OrXFilter(
            $this->createCallbackWithReturnBool(false),
            $this->createCallbackWithReturnBool(true),
            $this->createCallbackWithReturnBool(false)
        );

        $result = $filter->__invoke($this->createDefinition());

        self::assertTrue($result);
    }

    #[Test]
    public function shouldReturnFalseIfAllClosuresReturnFalse(): void
    {
        $filter = new OrXFilter(
            $this->createCallbackWithReturnBool(false),
            $this->createCallbackWithReturnBool(false),
            $this->createCallbackWithReturnBool(false)
        );

        $result = $filter->__invoke($this->createDefinition());

        self::assertFalse($result);
    }

    /**
     * Create callable with return bool
     *
     * @param bool $return
     *
     * @return callable
     */
    private function createCallbackWithReturnBool(bool $return): callable
    {
        return function () use ($return) {
            return $return;
        };
    }

    /**
     * Create definition
     *
     * @return CheckDefinition
     */
    private function createDefinition(): CheckDefinition
    {
        return new CheckDefinition(\uniqid((string) \random_int(0, PHP_INT_MAX), true), $this->createMock(CheckInterface::class), []);
    }
}
