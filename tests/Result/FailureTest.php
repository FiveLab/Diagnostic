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

namespace FiveLab\Component\Diagnostic\Tests\Result;

use FiveLab\Component\Diagnostic\Result\Failure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FailureTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreate(): void
    {
        $result = new Failure('some foo');

        self::assertEquals('some foo', $result->getMessage());
    }
}
