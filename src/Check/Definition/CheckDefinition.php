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

namespace FiveLab\Component\Diagnostic\Check\Definition;

use FiveLab\Component\Diagnostic\Check\CheckInterface;

/**
 * Default check definition.
 */
class CheckDefinition implements CheckDefinitionInterface
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var CheckInterface
     */
    private $check;

    /**
     * @var array
     */
    private $groups;

    /**
     * Constructor.
     *
     * @param string         $key
     * @param CheckInterface $check
     * @param array          $groups
     */
    public function __construct(string $key, CheckInterface $check, array $groups)
    {
        $this->key = $key;
        $this->check = $check;
        $this->groups = $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function getCheck(): CheckInterface
    {
        return $this->check;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
