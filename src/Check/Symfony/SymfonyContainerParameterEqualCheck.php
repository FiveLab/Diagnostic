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

namespace FiveLab\Component\Diagnostic\Check\Symfony;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\ParameterEqualCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SymfonyContainerParameterEqualCheck implements CheckInterface
{
    /**
     * @var \Closure(mixed, mixed): CheckInterface
     */
    private \Closure $parameterEqualCheckFactory;

    /**
     * @var array<string, string>
     */
    private array $extra = [];

    public function __construct(private readonly ContainerInterface $container, private readonly string $parameterName, private readonly mixed $expectedValue)
    {
        $this->parameterEqualCheckFactory = static function (mixed $expected, mixed $actual) {
            return new ParameterEqualCheck($expected, $actual);
        };
    }

    public function getExtraParameters(): array
    {
        $extra = [
            'parameter name' => $this->parameterName,
        ];

        return \array_merge($extra, $this->extra);
    }

    public function check(): Result
    {
        if (!$this->container->hasParameter($this->parameterName)) {
            return new Failure('The parameter was not found.');
        }

        $parameterValue = $this->container->getParameter($this->parameterName);

        $parameterEqualCheck = \call_user_func($this->parameterEqualCheckFactory, $this->expectedValue, $parameterValue);

        $result = $parameterEqualCheck->check();

        $this->extra = $parameterEqualCheck->getExtraParameters();

        return $result;
    }

    public function setParameterEqualCheckFactory(\Closure $factory): void
    {
        $this->parameterEqualCheckFactory = $factory;
    }
}
