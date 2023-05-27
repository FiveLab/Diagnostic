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

namespace FiveLab\Component\Diagnostic\Check\Environment;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Existence ENV variable in system check
 */
readonly class EnvExistenceCheck implements CheckInterface
{
    /**
     * Constructor.
     *
     * @param string $envName
     */
    public function __construct(private string $envName)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function check(): Result
    {
        if (false === \getenv($this->envName)) {
            return new Failure(\sprintf(
                'Variable "%s" does not exist in ENV.',
                $this->envName
            ));
        }

        return new Success(\sprintf(
            'Variable "%s" exist in ENV.',
            $this->envName
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'env' => $this->envName,
        ];
    }
}
