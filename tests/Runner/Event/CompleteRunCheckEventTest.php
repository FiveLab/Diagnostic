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

namespace FiveLab\Component\Diagnostic\Tests\Runner\Event;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinition;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Runner\Event\CompleteRunCheckEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CompleteRunCheckEventTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreate(): void
    {
        $definition = new CheckDefinition('', $this->createMock(CheckInterface::class), []);
        $result = $this->createMock(Result::class);

        $event = new CompleteRunCheckEvent($definition, $result);

        self::assertEquals($definition, $event->definition);
        self::assertEquals($result, $event->result);
    }
}
