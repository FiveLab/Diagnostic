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

namespace FiveLab\Component\Diagnostic\Tests\Check;

use FiveLab\Component\Diagnostic\Check\ParameterEqualCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use PHPUnit\Framework\TestCase;

class ParameterEqualCheckTest extends TestCase
{
    /**
     * @test
     *
     * @param mixed           $expected
     * @param mixed           $actual
     * @param ResultInterface $expectedResult
     * @param array           $expectedExtra
     *
     * @dataProvider provideParameters
     */
    public function shouldSuccessCheck($expected, $actual, ResultInterface $expectedResult, array $expectedExtra): void
    {
        $check = new ParameterEqualCheck($expected, $actual);

        $actualResult = $check->check();

        self::assertEquals($expectedResult, $actualResult);
        self::assertEquals($expectedExtra, $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfExpectedNotSupported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid expected value. Must be a array or scalar, but "object" given.');

        new ParameterEqualCheck(new \stdClass(), []);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfActualNotSupported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid actual value. Must be a array or scalar, but "object" given.');

        new ParameterEqualCheck([], new \stdClass());
    }

    /**
     * Provide parameters for testing
     *
     * @return array
     */
    public function provideParameters(): array
    {
        return [
            // Arrays
            'array: random arrays' => [
                ['some' => 'some', 'bar' => 'foo', 'foo' => 'bar'],
                ['bar' => 'foo', 'some' => 'some', 'foo' => 'bar'],
                new Success('The parameters is equals.'),
                [
                    'type'     => 'array',
                    'expected' => ['bar' => 'foo', 'foo' => 'bar', 'some' => 'some'],
                    'actual'   => ['bar' => 'foo', 'foo' => 'bar', 'some' => 'some'],
                ],
            ],

            'array: simple arrays' => [
                [1, 2, 'foo', 4],
                [4, 2, 1, 'foo'],
                new Success('The parameters is equals.'),
                [
                    'type'     => 'array',
                    'expected' => [1, 2, 'foo', 4],
                    'actual'   => [4, 2, 1, 'foo'],
                ],
            ],

            'array: fail if actual is not array' => [
                [1, 2],
                'some',
                new Failure('The expected parameters must be a array, but "string" given.'),
                [
                    'type'     => 'array',
                    'expected' => [1, 2],
                    'actual'   => 'some',
                ],
            ],

            'array: fail if arrays does not equal'     => [
                [1, 2],
                [1, 2, 3],
                new Failure('The parameters does not equals.'),
                [
                    'type'     => 'array',
                    'expected' => [1, 2],
                    'actual'   => [1, 2, 3],
                ],
            ],

            // Boolean
            'bool: should success convert true string' => [
                true,
                'true',
                new Success('The parameters is equals.'),
                [
                    'type'     => 'boolean',
                    'expected' => true,
                    'actual'   => 'true',
                ],
            ],

            'bool: should success convert TRUE string' => [
                true,
                'TRUE',
                new Success('The parameters is equals.'),
                [
                    'type'     => 'boolean',
                    'expected' => true,
                    'actual'   => 'TRUE',
                ],
            ],

            'bool: should success convert false string' => [
                false,
                'false',
                new Success('The parameters is equals.'),
                [
                    'type'     => 'boolean',
                    'expected' => false,
                    'actual'   => 'false',
                ],
            ],

            'bool: should success convert FALSE string' => [
                false,
                'FALSE',
                new Success('The parameters is equals.'),
                [
                    'type'     => 'boolean',
                    'expected' => false,
                    'actual'   => 'FALSE',
                ],
            ],

            'bool: should success convert 1 to bool' => [
                true,
                '1',
                new Success('The parameters is equals.'),
                [
                    'type'     => 'boolean',
                    'expected' => true,
                    'actual'   => '1',
                ],
            ],

            'bool: should success convert 0 to bool' => [
                false,
                '0',
                new Success('The parameters is equals.'),
                [
                    'type'     => 'boolean',
                    'expected' => false,
                    'actual'   => '0',
                ],
            ],

            'bool: failure'                            => [
                false,
                true,
                new Failure('The parameters does not equals.'),
                [
                    'type'     => 'boolean',
                    'expected' => false,
                    'actual'   => true,
                ],
            ],

            // Null
            'null: should success convert null string' => [
                null,
                'null',
                new Success('The parameters is equals.'),
                [
                    'type'     => 'null',
                    'expected' => null,
                    'actual'   => 'null',
                ],
            ],

            'null: should success convert NULL string' => [
                null,
                'NULL',
                new Success('The parameters is equals.'),
                [
                    'type'     => 'null',
                    'expected' => null,
                    'actual'   => 'NULL',
                ],
            ],

            'null: success' => [
                null,
                null,
                new Success('The parameters is equals.'),
                [
                    'type'     => 'null',
                    'expected' => null,
                    'actual'   => null,
                ],
            ],

            'null: failure' => [
                null,
                '',
                new Failure('The parameters does not equals.'),
                [
                    'type'     => 'null',
                    'expected' => null,
                    'actual'   => '',
                ],
            ],

            // Integer
            'int: success'  => [
                123,
                123,
                new Success('The parameters is equals.'),
                [
                    'type'     => 'integer',
                    'expected' => 123,
                    'actual'   => 123,
                ],
            ],

            'int: success if pass actual as string' => [
                321,
                '321',
                new Success('The parameters is equals.'),
                [
                    'type'     => 'integer',
                    'expected' => 321,
                    'actual'   => '321',
                ],
            ],

            'int: success if pass expected as string' => [
                '11',
                11,
                new Success('The parameters is equals.'),
                [
                    'type'     => 'integer',
                    'expected' => '11',
                    'actual'   => 11,
                ],
            ],

            'int: failure if pass float' => [
                321,
                321.11,
                new Failure('Actual parameter is not integer.'),
                [
                    'type'     => 'integer',
                    'expected' => 321,
                    'actual'   => 321.11,
                ],
            ],

            'int: failure'    => [
                55,
                555,
                new Failure('The parameters does not equals.'),
                [
                    'type'     => 'integer',
                    'expected' => 55,
                    'actual'   => 555,
                ],
            ],

            // Float/Double
            'double: success' => [
                11.11,
                11.11,
                new Success('The parameters is equals.'),
                [
                    'type'     => 'double',
                    'expected' => 11.11,
                    'actual'   => 11.11,
                ],
            ],

            'double: success with difference precision' => [
                12.22,
                12.2245123,
                new Success('The parameters is equals.'),
                [
                    'type'     => 'double',
                    'expected' => 12.22,
                    'actual'   => 12.2245123,
                ],
            ],

            'double: success if pass actual as string' => [
                321.33,
                '321.33',
                new Success('The parameters is equals.'),
                [
                    'type'     => 'double',
                    'expected' => 321.33,
                    'actual'   => '321.33',
                ],
            ],

            'double: success if pass expected as string' => [
                '11.22',
                11.22,
                new Success('The parameters is equals.'),
                [
                    'type'     => 'double',
                    'expected' => '11.22',
                    'actual'   => 11.22,
                ],
            ],

            'double: failure if pass int' => [
                321.11,
                321,
                new Failure('Actual parameter is not double.'),
                [
                    'type'     => 'double',
                    'expected' => 321.11,
                    'actual'   => 321,
                ],
            ],

            'double: failure' => [
                55.55,
                55.54,
                new Failure('The parameters does not equals.'),
                [
                    'type'     => 'double',
                    'expected' => 55.55,
                    'actual'   => 55.54,
                ],
            ],

            // Scalar/String
            'string: success' => [
                'some foo',
                'some foo',
                new Success('The parameters is equals.'),
                [
                    'type'     => 'string',
                    'expected' => 'some foo',
                    'actual'   => 'some foo',
                ],
            ],

            'string: failure' => [
                'some foo',
                'some bar',
                new Failure('The parameters does not equals.'),
                [
                    'type'     => 'string',
                    'expected' => 'some foo',
                    'actual'   => 'some bar',
                ],
            ],
        ];
    }
}
