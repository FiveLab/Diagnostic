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
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Result\Warning;

/**
 * Check what the path is writable.
 */
class PathWritableCheck implements CheckInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var bool
     */
    private $strict;

    /**
     * Constructor.
     *
     * @param string $path
     * @param bool   $strict
     */
    public function __construct(string $path, bool $strict = true)
    {
        $this->path = $path;
        $this->strict = $strict;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        if (!\file_exists($this->path)) {
            if ($this->strict) {
                return new Failure('The path not exist.');
            }

            return new Warning('The path not exist.');
        }

        $state = \is_file($this->path) ? 'file' : 'directory';

        if (\is_writable($this->path)) {
            return new Success(\sprintf('The %s is writable.', $state));
        }

        return new Failure(\sprintf('The %s is not writable.', $state));
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'path' => $this->path,
        ];
    }
}
