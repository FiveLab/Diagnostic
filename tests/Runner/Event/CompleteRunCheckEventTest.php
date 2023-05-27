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

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionInterface;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Runner\Event\CompleteRunCheckEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CompleteRunCheckEventTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreate(): void
    {
        /** @var CheckDefinitionInterface $definition */
        $definition = $this->createMock(CheckDefinitionInterface::class);

        /** @var ResultInterface $result */
        $result = $this->createMock(ResultInterface::class);

        $event = new CompleteRunCheckEvent($definition, $result);

        self::assertEquals($definition, $event->getDefinition());
        self::assertEquals($result, $event->getResult());
    }
}
