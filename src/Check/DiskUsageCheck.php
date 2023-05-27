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
 * Check disk usage.
 */
readonly class DiskUsageCheck implements CheckInterface
{
    /**
     * Constructor.
     *
     * @param int    $criticalThreshold
     * @param int    $warningThreshold
     * @param string $path
     */
    public function __construct(private int $criticalThreshold, private int $warningThreshold, private string $path = '/')
    {
        if ($criticalThreshold < 0 || $criticalThreshold > 100) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid critical threshold "%d". Should be between 0 and 100.',
                $criticalThreshold
            ));
        }

        if ($warningThreshold < 0 || $warningThreshold > 100) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid warning threshold "%d". Should be between 0 and 100.',
                $warningThreshold
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function check(): Result
    {
        $freeSpace = \disk_free_space($this->path);
        $totalSpace = \disk_total_space($this->path);
        $usedSpace = $totalSpace - $freeSpace;
        $usedInPercent = ($usedSpace / $totalSpace) * 100;

        if ($usedInPercent >= $this->criticalThreshold) {
            return new Failure(\sprintf('Disk usage too high: %2d percent.', $usedInPercent));
        }

        if ($usedInPercent >= $this->warningThreshold) {
            return new Failure(\sprintf('Disk usage high: %2d percent.', $usedInPercent));
        }

        return new Success(\sprintf('Disk usage is %2d percent.', $usedInPercent));
    }
}
