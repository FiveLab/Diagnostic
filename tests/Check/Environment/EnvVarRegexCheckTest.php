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

namespace FiveLab\Component\Diagnostic\Tests\Check\Environment;

use FiveLab\Component\Diagnostic\Check\Environment\EnvVarRegexCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use PHPUnit\Framework\TestCase;

class EnvVarRegexCheckTest extends TestCase
{
    private const ENV_VAR_NAME = 'FOO_ENV';

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (getenv(self::ENV_VAR_NAME)) {
            self::fail('Environment variable which name is used for testing shouldn\'t exist before test');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        putenv(self::ENV_VAR_NAME);
    }

    /**
     * @test
     * @dataProvider provideParameters
     *
     * @param string          $variableValue
     * @param string          $pattern
     * @param ResultInterface $expectedResult
     * @param array           $expectedExtra
     */
    public function shouldSuccessCheck(string $variableValue, string $pattern, ResultInterface $expectedResult, array $expectedExtra): void
    {
        putenv(sprintf('%s=%s', self::ENV_VAR_NAME, $variableValue));
        $check = new EnvVarRegexCheck(self::ENV_VAR_NAME, $pattern);

        $actualResult = $check->check();

        self::assertEquals($expectedResult, $actualResult);
        self::assertEquals($expectedExtra, $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForEmpyVariableName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Environment variable name should not be empty.');

        new EnvVarRegexCheck('', '/^BAR$/');
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForInvalidRegexPattern(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex pattern.');

        new EnvVarRegexCheck(self::ENV_VAR_NAME, 'NOT_A_REGEX_PATTERN');
    }

    /**
     * @test
     */
    public function shouldFailForUnsetVariable(): void
    {
        $pattern = '/^BAR$/';
        $expectedResult = new Failure('Environment variable is not set.');
        $expectedExtra = ['variableName' => self::ENV_VAR_NAME, 'pattern' => $pattern];

        $check = new EnvVarRegexCheck(self::ENV_VAR_NAME, $pattern);
        $actualResult = $check->check();

        self::assertEquals($expectedResult, $actualResult);
        self::assertEquals($expectedExtra, $check->getExtraParameters());
    }

    /**
     * Provide parameters for testing
     *
     * @return array
     */
    public function provideParameters(): array
    {
        return [
            'envvar matches full pattern'         => [
                'BAR',
                '/^BAR$/',
                new Success('Environment variable matches pattern.'),
                [
                    'variableName'  => self::ENV_VAR_NAME,
                    'pattern'       => '/^BAR$/',
                    'variableValue' => 'BAR',
                ],
            ],
            'envvar matches in_array pattern'     => [
                'BAR',
                '/^\b(BAR|BAZ)\b$/',
                new Success('Environment variable matches pattern.'),
                [
                    'variableName'  => self::ENV_VAR_NAME,
                    'pattern'       => '/^\b(BAR|BAZ)\b$/',
                    'variableValue' => 'BAR',
                ],
            ],
            'envvar not matches in_array pattern' => [
                'BAR',
                '/^\b(EVIL|GOOD)\b$/',
                new Failure('Environment variable does not match pattern.'),
                [
                    'variableName'  => self::ENV_VAR_NAME,
                    'pattern'       => '/^\b(EVIL|GOOD)\b$/',
                    'variableValue' => 'BAR',
                ],
            ],
        ];
    }
}
