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

namespace FiveLab\Component\Diagnostic\Tests\Check\Definition\Filter;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinition;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\CheckDefinitionsInGroupFilter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckDefinitionsInGroupFilterTest extends TestCase
{
    #[Test]
    public function shouldFilter(): void
    {
        /** @var CheckInterface $check */
        $check = $this->createMock(CheckInterface::class);

        $definition = new CheckDefinition('some', $check, ['foo', 'bar', 'qwerty']);

        $filter = new CheckDefinitionsInGroupFilter('bar');

        self::assertTrue($filter->__invoke($definition));
    }

    #[Test]
    public function shouldNoFilter(): void
    {
        /** @var CheckInterface $check */
        $check = $this->createMock(CheckInterface::class);

        $definition = new CheckDefinition('some', $check, ['foo', 'bar', 'qwerty']);

        $filter = new CheckDefinitionsInGroupFilter('some');

        self::assertFalse($filter->__invoke($definition));
    }
}
