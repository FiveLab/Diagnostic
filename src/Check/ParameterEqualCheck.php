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

namespace FiveLab\Component\Diagnostic\Check;

use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check the parameter equals.
 */
class ParameterEqualCheck implements CheckInterface
{
    /**
     * @var array<mixed>
     */
    private array $extra;

    /**
     * Constructor.
     *
     * @param mixed $expected
     * @param mixed $actual
     */
    public function __construct(private readonly mixed $expected, private readonly mixed $actual)
    {
        if ($expected && !\is_array($expected) && !\is_scalar($expected)) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid expected value. Must be a array or scalar, but "%s" given.',
                \gettype($expected)
            ));
        }

        if ($actual && !\is_array($actual) && !\is_scalar($actual)) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid actual value. Must be a array or scalar, but "%s" given.',
                \gettype($actual)
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return $this->extra;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): Result
    {
        $this->extra['expected'] = $this->expected;
        $this->extra['actual'] = $this->actual;

        $expected = $this->expected;
        $actual = $this->actual;

        // Arrays
        if (\is_array($expected)) {
            $this->extra['type'] = 'array';

            if (!\is_array($actual)) {
                return new Failure(\sprintf(
                    'The expected parameters must be a array, but "%s" given.',
                    \gettype($actual)
                ));
            }

            if (\range(0, \count($expected) - 1) === \array_keys($expected)) {
                // Simple list
                \sort($expected);
                \sort($actual);
            } else {
                // Associative array
                \asort($expected);
                \asort($actual);
            }

            if ($actual !== $expected) {
                return new Failure('The parameters does not equals.');
            }

            return new Success('The parameters is equals.');
        }

        // Boolean
        if (\is_bool($expected)) {
            $this->extra['type'] = 'boolean';

            if ('1' === (string) $actual || 'true' === \strtolower((string) $actual)) {
                $actual = true;
            }

            if ('0' === (string) $actual || 'false' === \strtolower((string) $actual)) {
                $actual = false;
            }

            if ($actual !== $expected) {
                return new Failure('The parameters does not equals.');
            }

            return new Success('The parameters is equals.');
        }

        // Null
        if (\is_null($expected)) {
            $this->extra['type'] = 'null';

            if (\is_string($actual) && 'null' === \strtolower($actual)) {
                $actual = null;
            }

            if ($actual !== $expected) {
                return new Failure('The parameters does not equals.');
            }

            return new Success('The parameters is equals.');
        }

        // Integer
        if (\preg_match('/^\d+$/', (string) $expected)) {
            $this->extra['type'] = 'integer';

            if (!\preg_match('/^\d+$/', (string) $actual)) {
                return new Failure('Actual parameter is not integer.');
            }

            $expected = (int) $expected;
            $actual = (int) $actual;

            if ($actual !== $expected) {
                return new Failure('The parameters does not equals.');
            }

            return new Success('The parameters is equals.');
        }

        // Double/Float
        if (\preg_match('/^\d+\.\d+$/', (string) $expected)) {
            $this->extra['type'] = 'double';

            if (!\preg_match('/^\d+\.\d+$/', (string) $actual)) {
                return new Failure('Actual parameter is not double.');
            }

            $doubleParts = \explode('.', (string) $expected);
            $precision = \strlen($doubleParts[0]);

            $expected = (string) $expected;
            $actual = (string) \round((float) $actual, $precision);

            if ($expected !== $actual) {
                return new Failure('The parameters does not equals.');
            }

            return new Success('The parameters is equals.');
        }

        // Scalar/String
        $this->extra['type'] = 'string';

        if ($actual !== $expected) {
            return new Failure('The parameters does not equals.');
        }

        return new Success('The parameters is equals.');
    }
}
