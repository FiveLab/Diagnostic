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

namespace FiveLab\Component\Diagnostic\Check\PhpIni;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check php.ini parameter.
 */
class PhpIniParameterCheck implements CheckInterface
{
    /**
     * @var string
     */
    private string $actualValue;

    /**
     * Constructor.
     *
     * @param string $parameter
     * @param string $expectedValue
     */
    public function __construct(private readonly string $parameter, private readonly string $expectedValue)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function check(): Result
    {
        $actualValue = \ini_get($this->parameter);

        if (false === $actualValue) {
            // The parameter was not found in settings.
            return new Failure('The parameter was not found in configuration.');
        }

        $this->actualValue = $actualValue;

        if ($this->actualValue !== $this->expectedValue) {
            return new Failure('Fail check php.ini parameter.');
        }

        return new Success('Success check php.ini parameter.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'parameter' => $this->parameter,
            'expected'  => $this->expectedValue,
            'actual'    => $this->actualValue,
        ];
    }
}
