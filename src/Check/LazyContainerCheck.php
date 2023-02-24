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

use FiveLab\Component\Diagnostic\Result\ResultInterface;
use Psr\Container\ContainerInterface;

/**
 * Lazy check. Previously get check from container.
 * Note: for Symfony, the check service must be "public".
 */
class LazyContainerCheck implements CheckInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var string
     */
    private string $id;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param string             $id
     */
    public function __construct(ContainerInterface $container, string $id)
    {
        $this->container = $container;
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        return $this->getCheck()->check();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return $this->getCheck()->getExtraParameters();
    }

    /**
     * Get original check
     *
     * @return CheckInterface
     */
    private function getCheck(): CheckInterface
    {
        return $this->container->get($this->id);
    }
}
