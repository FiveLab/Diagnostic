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

namespace FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpExt;

use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check connect to RabbitMQ
 */
class RabbitMqConnectionCheck extends AbstractRabbitMqCheck
{
    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        $failure = $this->connect();

        if ($failure) {
            return $failure;
        }

        return new Success('Success connect to RabbitMQ');
    }
}
