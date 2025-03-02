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

namespace FiveLab\Component\Diagnostic\Tests\Check\Environment;

use FiveLab\Component\Diagnostic\Check\Environment\EnvExistenceCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnvExistenceCheckTest extends TestCase
{
    private const ENV_VAR_NAME = 'FOO_ENV';

    private EnvExistenceCheck $check;

    protected function setUp(): void
    {
        $this->check = new EnvExistenceCheck(self::ENV_VAR_NAME);

        \putenv(\sprintf('%s=foo', self::ENV_VAR_NAME));
    }

    protected function tearDown(): void
    {
        \putenv(self::ENV_VAR_NAME);
    }

    #[Test]
    public function shouldSuccessGetExtra(): void
    {
        self::assertEquals([
            'env' => self::ENV_VAR_NAME,
        ], $this->check->getExtraParameters());
    }

    #[Test]
    public function shouldSuccessCheck(): void
    {
        $result = $this->check->check();

        self::assertEquals(new Success('Variable "FOO_ENV" exist in ENV.'), $result);
    }

    #[Test]
    public function shouldFailCheck(): void
    {
        \putenv(self::ENV_VAR_NAME);

        $result = $this->check->check();

        self::assertEquals(new Failure('Variable "FOO_ENV" does not exist in ENV.'), $result);
    }
}
