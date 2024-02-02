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

use FiveLab\Component\Diagnostic\Util\ArrayUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ArrayUtilsTest extends TestCase
{
    #[Test]
    #[DataProvider('provideSettings')]
    public function shouldSuccessTryGetSpecificSettingFromSettings(string $path, array $settings, mixed $expected): void
    {
        if ($expected instanceof \Throwable) {
            $this->expectException(\get_class($expected));
            $this->expectExceptionMessage($expected->getMessage());
        }

        $actual = ArrayUtils::tryGetSpecificSettingFromSettings($path, $settings);

        self::assertEquals($actual, $expected);
    }

    /**
     * Provide settings for testing
     *
     * @return array
     */
    public static function provideSettings(): array
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
                'd',
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
                new \UnexpectedValueException('The setting "a.b.c.d" is missed.'),
            ],
        ];
    }
}
