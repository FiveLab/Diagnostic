<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Symfony;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\ParameterEqualCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check the parameter from symfony container.
 */
class SymfonyContainerParameterEqualCheck implements CheckInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $parameterName;

    /**
     * @var mixed
     */
    private $expectedValue;

    /**
     * @var \Closure
     */
    private $parameterEqualCheckFactory;

    /**
     * @var array
     */
    private $extra = [];

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param string             $parameterName
     * @param mixed              $expectedValue
     */
    public function __construct(ContainerInterface $container, string $parameterName, $expectedValue)
    {
        $this->container = $container;
        $this->parameterName = $parameterName;
        $this->expectedValue = $expectedValue;

        $this->parameterEqualCheckFactory = function ($expected, $actual) {
            return new ParameterEqualCheck($expected, $actual);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $extra = [
            'parameter name' => $this->parameterName,
        ];

        $extra = \array_merge($extra, $this->extra);

        return $extra;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
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

    /**
     * Set factory for create parmaeter check
     *
     * @param \Closure $factory
     */
    public function setParameterEqualCheckFactory(\Closure $factory): void
    {
        $this->parameterEqualCheckFactory = $factory;
    }
}
