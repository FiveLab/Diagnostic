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

use FiveLab\Component\Diagnostic\Check\IsJsonCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use PHPUnit\Framework\TestCase;

class IsJsonCheckTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessGetExtraParams(): void
    {
        $check = new IsJsonCheck('{"foo": "bar"}');
        $params = $check->getExtraParameters();

        self::assertEquals([
            'json' => '{"foo": "bar"}',
            'type' => null,
        ], $params);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParamsWithType(): void
    {
        $check = new IsJsonCheck('{"foo": "bar"}', 'array');
        $params = $check->getExtraParameters();

        self::assertEquals([
            'json' => '{"foo": "bar"}',
            'type' => 'array',
        ], $params);
    }

    /**
     * @test
     */
    public function shouldFailIfTypeIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type "foo-bar". The function "is_foo-bar" does not exist.');

        new IsJsonCheck('[]', 'foo-bar');
    }

    /**
     * @test
     */
    public function shouldSuccessCheckForCorrectJson(): void
    {
        $check = new IsJsonCheck('{"foo": "bar"}');
        $result = $check->check();

        self::assertEquals(new Success('The input data is correct json.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessCheckWithCorrectJsonAndType(): void
    {
        $check = new IsJsonCheck('{"foo": "bar"}', 'array');
        $result = $check->check();

        self::assertEquals(new Success('The input data is correct json and "array".'), $result);
    }

    /**
     * @test
     */
    public function shouldFailForIncorrectJson(): void
    {
        $check = new IsJsonCheck('{"foo": "bar}');
        $result = $check->check();

        self::assertEquals(new Failure('The input data is\'t json. Error: Control character error, possibly incorrectly encoded.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailForIncorrectType(): void
    {
        $check = new IsJsonCheck('"foo bar"', 'numeric');
        $result = $check->check();

        self::assertEquals(new Failure('The parsed JSON is not "numeric".'), $result);
    }
}
