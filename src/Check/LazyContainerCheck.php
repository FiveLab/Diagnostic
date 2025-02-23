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

use FiveLab\Component\Diagnostic\Result\Result;
use Psr\Container\ContainerInterface;

/**
 * Lazy check. Previously get check from container.
 * Note: for Symfony, the check service must be "public".
 */
readonly class LazyContainerCheck implements CheckInterface
{
    public function __construct(private ContainerInterface $container, private string $id)
    {
    }

    public function check(): Result
    {
        return $this->getCheck()->check();
    }

    public function getExtraParameters(): array
    {
        return $this->getCheck()->getExtraParameters();
    }

    private function getCheck(): CheckInterface
    {
        return $this->container->get($this->id);
    }
}
