<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check\Definition\Filter;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\OrXFilter;
use PHPUnit\Framework\TestCase;

class OrXFilterTest extends TestCase
{
    /**
     * @test
     */
    public function shouldReturnFalseWithoutAnyConditions(): void
    {
        $filter = new OrXFilter();

        $result = $filter->__invoke($this->createDefinition());

        self::assertFalse($result);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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
     * @return CheckDefinitionInterface
     */
    private function createDefinition(): CheckDefinitionInterface
    {
        return $this->createMock(CheckDefinitionInterface::class);
    }
}
