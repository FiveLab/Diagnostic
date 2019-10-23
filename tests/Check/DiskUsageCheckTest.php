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

use FiveLab\Component\Diagnostic\Check\DiskUsageCheck;
use PHPUnit\Framework\TestCase;

class DiskUsageCheckTest extends TestCase
{
    /**
     * @param int    $criticalThreshold
     * @param int    $warningThreshold
     * @param string $message
     *
     * @test
     *
     * @dataProvider provideInvalidThresholds
     */
    public function shouldThrowExceptionForInvalidThreshold(int $criticalThreshold, int $warningThreshold, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new DiskUsageCheck($criticalThreshold, $warningThreshold);
    }

    /**
     * Provide data for test invalid thresholds.
     *
     * @return array
     */
    public function provideInvalidThresholds(): array
    {
        return [
            'warning less than 0'   => [0, -1, 'Invalid warning threshold "-1". Should be between 0 and 100.'],
            'warning more than 100' => [0, 101, 'Invalid warning threshold "101". Should be between 0 and 100'],

            'critical less than 0'   => [-1, 0, 'Invalid critical threshold "-1". Should be between 0 and 100.'],
            'critical more than 100' => [101, 0, 'Invalid critical threshold "101". Should be between 0 and 100.'],
        ];
    }
}
