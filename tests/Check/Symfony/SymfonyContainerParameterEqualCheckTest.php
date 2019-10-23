<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check\Symfony;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\Symfony\SymfonyContainerParameterEqualCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SymfonyContainerParameterEqualCheckTest extends TestCase
{
    /**
     * @test
     */
    public function shouldFailureIfParameterNotFound(): void
    {
        $container = new ContainerBuilder();

        $check = new SymfonyContainerParameterEqualCheck($container, 'some', 'foo');

        $result = $check->check();

        self::assertEquals(new Failure('The parameter was not found.'), $result);
        self::assertEquals([
            'parameter name' => 'some',
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldSuccessCheck(): void
    {
        $parameterCheck = $this->createMock(CheckInterface::class);

        $parameterCheck->expects(self::once())
            ->method('check')
            ->willReturn(new Success('some foo bar'));

        $parameterCheck->expects(self::once())
            ->method('getExtraParameters')
            ->willReturn(['foo' => 'bar']);

        $container = new ContainerBuilder();
        $container->setParameter('some', 'bar');

        $check = new SymfonyContainerParameterEqualCheck($container, 'some', 'bar');

        $check->setParameterEqualCheckFactory(function ($expected, $actual) use ($parameterCheck) {
            self::assertEquals('bar', $expected);
            self::assertEquals('bar', $actual);

            return $parameterCheck;
        });

        $result = $check->check();

        self::assertEquals(new Success('some foo bar'), $result);
        self::assertEquals([
            'parameter name' => 'some',
            'foo'            => 'bar',
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldFailCheck(): void
    {
        $parameterCheck = $this->createMock(CheckInterface::class);

        $parameterCheck->expects(self::once())
            ->method('check')
            ->willReturn(new Failure('some foo bar'));

        $parameterCheck->expects(self::once())
            ->method('getExtraParameters')
            ->willReturn(['foo' => 'bar']);

        $container = new ContainerBuilder();
        $container->setParameter('some', 'foo');

        $check = new SymfonyContainerParameterEqualCheck($container, 'some', 'bar');

        $check->setParameterEqualCheckFactory(function ($expected, $actual) use ($parameterCheck) {
            self::assertEquals('bar', $expected);
            self::assertEquals('foo', $actual);

            return $parameterCheck;
        });

        $result = $check->check();

        self::assertEquals(new Failure('some foo bar'), $result);
        self::assertEquals([
            'parameter name' => 'some',
            'foo'            => 'bar',
        ], $check->getExtraParameters());
    }
}
