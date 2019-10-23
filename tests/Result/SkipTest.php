<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Result;

use FiveLab\Component\Diagnostic\Result\Skip;
use PHPUnit\Framework\TestCase;

class SkipTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $result = new Skip('some foo');

        self::assertEquals('some foo', $result->getMessage());
    }
}
