<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check\Definition;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinition;
use PHPUnit\Framework\TestCase;

class CheckDefinitionTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        /** @var CheckInterface $check */
        $check = $this->createMock(CheckInterface::class);

        $definition = new CheckDefinition('some', $check, ['foo', 'bar']);

        self::assertEquals('some', $definition->getKey());
        self::assertEquals($check, $definition->getCheck());
        self::assertEquals(['foo', 'bar'], $definition->getGroups());
    }
}
