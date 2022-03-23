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

namespace FiveLab\Component\Diagnostic\Tests\Util;

use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Util\ArrayUtils;
use PHPUnit\Framework\TestCase;

class ArrayUtilsTest extends TestCase
{
    /**
     * @test
     *
     * @param string $path
     * @param array $settings
     * @param Failure|string $expected
     * @return void
     *
     * @dataProvider provideSettings
     */
    public function testTryGetSpecificSettingFromSettings(string $path, array $settings, $expected): void
    {
        $actual = ArrayUtils::tryGetSpecificSettingFromSettings($path, $settings);

        $this->assertEquals($actual, $expected);
    }

    /**
     * @return array
     */
    public function provideSettings(): array
    {
        return [
            [
                'a.b.c',
                [
                  'a' => [
                      'b' => [
                          'c' => 'd',
                      ],
                  ],
                ],
                'd'
            ],
            [
                'a.b.c.d.e.f.g.h',
                [
                    'a' => [
                        'b' => [
                            'c' => 'd',
                        ],
                    ],
                ],
                new Failure('The setting "a.b.c.d" is missed.'),
            ],
        ];
    }
}