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
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check whether an environment variable matches specific pattern
 */
class EnvVarRegexCheck implements CheckInterface
{
    /**
     * @var string
     */
    private string $variableName;

    /**
     * @var string
     */
    private string $pattern;

    /**
     * Constructor.
     *
     * @param string $variableName
     * @param string $pattern
     */
    public function __construct(string $variableName, string $pattern)
    {
        if (!$variableName) {
            throw new \InvalidArgumentException('Environment variable name should not be empty.');
        }

        if (!$this->isRegularExpression($pattern)) {
            throw new \InvalidArgumentException('Invalid regex pattern.');
        }

        $this->variableName = $variableName;
        $this->pattern = $pattern;
    }

    /**
     * {@inheritDoc}
     */
    public function check(): ResultInterface
    {
        if (!\getenv($this->variableName)) {
            return new Failure('Environment variable is not set.');
        }

        if (1 === \preg_match($this->pattern, (string) \getenv($this->variableName))) {
            return new Success('Environment variable matches pattern.');
        }

        return new Failure('Environment variable does not match pattern.');
    }

    /**
     * {@inheritDoc}
     */
    public function getExtraParameters(): array
    {
        $parameters = [
            'variableName' => $this->variableName,
            'pattern'      => $this->pattern,
        ];

        $value = \getenv($this->variableName);

        if (false !== $value) {
            $parameters['variableValue'] = $value;
        }

        return $parameters;
    }

    /**
     * @param string $pattern
     *
     * @return bool
     */
    private function isRegularExpression(string $pattern): bool
    {
        \set_error_handler(static function () {
        }, E_WARNING);

        $isRegularExpression = \preg_match($pattern, '') !== false;

        restore_error_handler();

        return $isRegularExpression;
    }
}
