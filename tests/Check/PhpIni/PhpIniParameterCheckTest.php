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

namespace FiveLab\Component\Diagnostic\Tests\Check\PhpIni;

use FiveLab\Component\Diagnostic\Check\PhpIni\PhpIniParameterCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use PHPUnit\Framework\TestCase;

class PhpIniParameterCheckTest extends TestCase
{
    /**
     * @var string
     */
    private $activeTimezone;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->activeTimezone = \ini_get('date.timezone');
        \ini_set('date.timezone', 'Europe/Kiev');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        \ini_set('date.timezone', $this->activeTimezone);
    }

    /**
     * @test
     */
    public function shouldSuccessCheck(): void
    {
        $check = new PhpIniParameterCheck('date.timezone', 'Europe/Kiev');

        $result = $check->check();

        self::assertEquals(new Success('Success check php.ini parameter.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtra(): void
    {
        $check = new PhpIniParameterCheck('date.timezone', 'Europe/Kiev');

        $check->check();

        self::assertEquals([
            'parameter' => 'date.timezone',
            'expected'  => 'Europe/Kiev',
            'actual'    => 'Europe/Kiev',
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldFailCheckIfParametersNotEquals(): void
    {
        $check = new PhpIniParameterCheck('date.timezone', 'UTC');

        $result = $check->check();

        self::assertEquals(new Failure('Fail check php.ini parameter.'), $result);

        self::assertEquals([
            'parameter' => 'date.timezone',
            'expected'  => 'UTC',
            'actual'    => 'Europe/Kiev',
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldFailCheckIfParametersWasNotFound(): void
    {
        $check = new PhpIniParameterCheck('some.foo.bar', 'Qwerty');

        $result = $check->check();

        self::assertEquals(new Failure('The parameter was not found in configuration.'), $result);
    }
}
