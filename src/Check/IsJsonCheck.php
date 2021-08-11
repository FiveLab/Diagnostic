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

/**
 * Check what the data is correct JSON.
 */
class IsJsonCheck implements CheckInterface
{
    /**
     * @var string
     */
    private string $json;

    /**
     * @var string|null
     */
    private ?string $expectedType;

    /**
     * Constructor.
     *
     * @param string      $json
     * @param string|null $expectedType
     */
    public function __construct(string $json, ?string $expectedType = null)
    {
        if ($expectedType) {
            $expectedFunction = 'is_'.$expectedType;

            if (!\function_exists($expectedFunction)) {
                throw new \InvalidArgumentException(\sprintf(
                    'Invalid type "%s". The function "%s" does not exist.',
                    $expectedType,
                    $expectedFunction
                ));
            }
        }

        $this->json = $json;
        $this->expectedType = $expectedType;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        $expectedFunction = 'is_'.$this->expectedType;

        try {
            $json = \json_decode($this->json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            return new Failure(\sprintf(
                'The input data is\'t json. Error: %s.',
                \rtrim($error->getMessage(), '.')
            ));
        }

        $extraTypeMessage = '';

        if ($this->expectedType) {
            $extraTypeMessage = \sprintf(' and "%s"', $this->expectedType);

            if (!$expectedFunction($json)) {
                return new Failure(\sprintf(
                    'The parsed JSON is not "%s".',
                    $this->expectedType
                ));
            }
        }

        return new Success(\sprintf(
            'The input data is correct json%s.',
            $extraTypeMessage
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'json' => $this->json,
            'type' => $this->expectedType,
        ];
    }
}