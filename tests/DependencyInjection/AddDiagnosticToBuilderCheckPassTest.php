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

namespace FiveLab\Component\Diagnostic\Tests\DependencyInjection;

use FiveLab\Component\Diagnostic\Check\Definition\CheckDefinitionsBuilder;
use FiveLab\Component\Diagnostic\Check\LazyContainerCheck;
use FiveLab\Component\Diagnostic\DependencyInjection\AddDiagnosticToBuilderCheckPass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AddDiagnosticToBuilderCheckPassTest extends TestCase
{
    #[Test]
    public function shouldNotProcessIfDefinitionsBuilderDoesNotExist(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setDefinition('some', new Definition());

        $compiler = new AddDiagnosticToBuilderCheckPass();

        $compiler->process($containerBuilder);

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function shouldSuccessProcessIfChecksDoesNotExist(): void
    {
        $containerBuilder = $this->createContainerBuilderWithDefinitionsBuilder();

        $compiler = new AddDiagnosticToBuilderCheckPass('diagnostic.definitions.builder');

        $compiler->process($containerBuilder);

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function shouldSuccessProcessWithOnlyTagName(): void
    {
        $containerBuilder = $this->createContainerBuilderWithDefinitionsBuilder();

        $checkContainerDefinition1 = new Definition(StubCheck::class);
        $checkContainerDefinition1->addTag('diagnostic.check');

        $checkContainerDefinition2 = new Definition(StubCheck::class);
        $checkContainerDefinition2->addTag('diagnostic.check');

        $containerBuilder->addDefinitions([
            'check_1' => $checkContainerDefinition1,
            'check_2' => $checkContainerDefinition2,
        ]);

        $compiler = new AddDiagnosticToBuilderCheckPass('diagnostic.definitions.builder', 'diagnostic.check');

        $compiler->process($containerBuilder);

        $definitionsBuilder = $containerBuilder->getDefinition('diagnostic.definitions.builder');

        self::assertEquals([
            [
                'addCheck',
                [
                    'check_1',
                    new Reference('check_1'),
                    '',
                    true,
                ],
            ],
            [
                'addCheck',
                [
                    'check_2',
                    new Reference('check_2'),
                    '',
                    true,
                ],
            ],
        ], $definitionsBuilder->getMethodCalls());
    }

    #[Test]
    public function shouldSuccessProcessWithGroups(): void
    {
        $containerBuilder = $this->createContainerBuilderWithDefinitionsBuilder();

        $checkContainerDefinition1 = new Definition(StubCheck::class);
        $checkContainerDefinition1->addTag('diagnostic.check', [
            'group' => 'foo',
        ]);

        $checkContainerDefinition2 = new Definition(StubCheck::class);
        $checkContainerDefinition2->addTag('diagnostic.check', [
            'group' => 'bar',
        ]);

        $containerBuilder->addDefinitions([
            'check_1' => $checkContainerDefinition1,
            'check_2' => $checkContainerDefinition2,
        ]);

        $compiler = new AddDiagnosticToBuilderCheckPass('diagnostic.definitions.builder', 'diagnostic.check');

        $compiler->process($containerBuilder);

        $definitionsBuilder = $containerBuilder->getDefinition('diagnostic.definitions.builder');

        self::assertEquals([
            [
                'addCheck',
                [
                    'check_1',
                    new Reference('check_1'),
                    'foo',
                    true,
                ],
            ],
            [
                'addCheck',
                [
                    'check_2',
                    new Reference('check_2'),
                    'bar',
                    true,
                ],
            ],
        ], $definitionsBuilder->getMethodCalls());
    }

    #[Test]
    public function shouldSuccessProcessWithCustomKeys(): void
    {
        $containerBuilder = $this->createContainerBuilderWithDefinitionsBuilder();

        $checkContainerDefinition1 = new Definition(StubCheck::class);
        $checkContainerDefinition1->addTag('diagnostic.check', [
            'key'              => 'foo',
            'error_on_failure' => true,
        ]);

        $checkContainerDefinition2 = new Definition(StubCheck::class);
        $checkContainerDefinition2->addTag('diagnostic.check', [
            'key'              => 'bar',
            'error_on_failure' => false,
        ]);

        $containerBuilder->addDefinitions([
            'check_1' => $checkContainerDefinition1,
            'check_2' => $checkContainerDefinition2,
        ]);

        $compiler = new AddDiagnosticToBuilderCheckPass('diagnostic.definitions.builder', 'diagnostic.check');

        $compiler->process($containerBuilder);

        $definitionsBuilder = $containerBuilder->getDefinition('diagnostic.definitions.builder');

        self::assertEquals([
            [
                'addCheck',
                [
                    'foo',
                    new Reference('check_1'),
                    '',
                    true,
                ],
            ],
            [
                'addCheck',
                [
                    'bar',
                    new Reference('check_2'),
                    '',
                    false,
                ],
            ],
        ], $definitionsBuilder->getMethodCalls());
    }

    #[Test]
    public function shouldSuccessWithMultipleTagsForOneService(): void
    {
        $containerBuilder = $this->createContainerBuilderWithDefinitionsBuilder();

        $checkContainerDefinition = new Definition(StubCheck::class);
        $checkContainerDefinition->addTag('diagnostic.check', [
            'group' => 'foo',
        ]);

        $checkContainerDefinition->addTag('diagnostic.check', [
            'group' => 'bar',
        ]);

        $containerBuilder->addDefinitions([
            'check_1' => $checkContainerDefinition,
        ]);

        $compiler = new AddDiagnosticToBuilderCheckPass('diagnostic.definitions.builder', 'diagnostic.check');

        $compiler->process($containerBuilder);

        $definitionsBuilder = $containerBuilder->getDefinition('diagnostic.definitions.builder');

        self::assertEquals([
            [
                'addCheck',
                [
                    'check_1',
                    new Reference('check_1'),
                    'foo',
                    true,
                ],
            ],
            [
                'addCheck',
                [
                    'check_1',
                    new Reference('check_1'),
                    'bar',
                    true,
                ],
            ],
        ], $definitionsBuilder->getMethodCalls());
    }

    #[Test]
    public function shouldSuccessWithResolveClassFromParameter(): void
    {
        $containerBuilder = $this->createContainerBuilderWithDefinitionsBuilder();
        $containerBuilder->setParameter('check_class', StubCheck::class);

        $checkContainerDefinition = new Definition('%check_class%');
        $checkContainerDefinition->addTag('diagnostic.check');

        $containerBuilder->setDefinition('check_1', $checkContainerDefinition);

        $compiler = new AddDiagnosticToBuilderCheckPass('diagnostic.definitions.builder', 'diagnostic.check');

        $compiler->process($containerBuilder);

        $definitionsBuilder = $containerBuilder->getDefinition('diagnostic.definitions.builder');

        self::assertEquals([
            [
                'addCheck',
                [
                    'check_1',
                    new Reference('check_1'),
                    '',
                    true,
                ],
            ],
        ], $definitionsBuilder->getMethodCalls());
    }

    #[Test]
    public function shouldSuccessProcessWithUseLazyDecorator(): void
    {
        $containerBuilder = $this->createContainerBuilderWithDefinitionsBuilder();

        $checkContainerDefinition = new Definition(StubCheck::class);
        $checkContainerDefinition->addTag('diagnostic.check');

        $containerBuilder->setDefinition('check', $checkContainerDefinition);

        $compiler = new AddDiagnosticToBuilderCheckPass('diagnostic.definitions.builder', 'diagnostic.check', true);

        $compiler->process($containerBuilder);

        // Check builder
        $definitionsBuilder = $containerBuilder->getDefinition('diagnostic.definitions.builder');

        self::assertEquals([
            [
                'addCheck',
                [
                    'check',
                    new Reference('check.lazy'),
                    '',
                    true,
                ],
            ],
        ], $definitionsBuilder->getMethodCalls());

        // Check lazy
        $lazyDefinition = $containerBuilder->getDefinition('check.lazy');

        self::assertEquals(LazyContainerCheck::class, $lazyDefinition->getClass());
        self::assertEquals([
            new Reference('service_container'),
            'check',
        ], $lazyDefinition->getArguments());
    }

    #[Test]
    public function shouldThrowExceptionIfParametersWasNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot compile diagnostic check with service id "check_1".');

        $containerBuilder = $this->createContainerBuilderWithDefinitionsBuilder();

        $checkContainerDefinition = new Definition('%check_class%');
        $checkContainerDefinition->addTag('diagnostic.check');

        $containerBuilder->setDefinition('check_1', $checkContainerDefinition);

        $compiler = new AddDiagnosticToBuilderCheckPass('diagnostic.definitions.builder', 'diagnostic.check');

        $compiler->process($containerBuilder);
    }

    #[Test]
    public function shouldThrowExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot compile diagnostic check with service id "check_1".');

        $containerBuilder = $this->createContainerBuilderWithDefinitionsBuilder();

        $checkContainerDefinition = new Definition('SomeClass');
        $checkContainerDefinition->addTag('diagnostic.check');

        $containerBuilder->setDefinition('check_1', $checkContainerDefinition);

        $compiler = new AddDiagnosticToBuilderCheckPass('diagnostic.definitions.builder', 'diagnostic.check');

        $compiler->process($containerBuilder);
    }

    #[Test]
    public function shouldThrowExceptionIfCheckNotImplementInterface(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot compile diagnostic check with service id "check_1".');

        $containerBuilder = $this->createContainerBuilderWithDefinitionsBuilder();

        $checkContainerDefinition = new Definition(\stdClass::class);
        $checkContainerDefinition->addTag('diagnostic.check');

        $containerBuilder->setDefinition('check_1', $checkContainerDefinition);

        $compiler = new AddDiagnosticToBuilderCheckPass('diagnostic.definitions.builder', 'diagnostic.check');

        $compiler->process($containerBuilder);
    }

    private function createContainerBuilderWithDefinitionsBuilder(): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setDefinition('diagnostic.definitions.builder', new Definition(CheckDefinitionsBuilder::class));

        return $containerBuilder;
    }
}
