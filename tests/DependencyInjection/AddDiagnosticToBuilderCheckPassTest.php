<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\DependencyInjection;

use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollectionBuilder;
use FiveLab\Component\Diagnostic\DependencyInjection\AddDiagnosticToBuilderCheckPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AddDiagnosticToBuilderCheckPassTest extends TestCase
{
    /**
     * @test
     */
    public function shouldNotProcessIfDefinitionsBuilderDoesNotExist(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setDefinition('some', new Definition());

        $compiler = new AddDiagnosticToBuilderCheckPass();

        $compiler->process($containerBuilder);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function shouldSuccessProcessIfChecksDoesNotExist(): void
    {
        $containerBuilder = $this->createContainerBuilderWithDefinitionsBuilder();

        $compiler = new AddDiagnosticToBuilderCheckPass('diagnostic.definitions.builder');

        $compiler->process($containerBuilder);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
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
                ],
            ],
            [
                'addCheck',
                [
                    'check_2',
                    new Reference('check_2'),
                    '',
                ],
            ],
        ], $definitionsBuilder->getMethodCalls());
    }

    /**
     * @test
     */
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
                ],
            ],
            [
                'addCheck',
                [
                    'check_2',
                    new Reference('check_2'),
                    'bar',
                ],
            ],
        ], $definitionsBuilder->getMethodCalls());
    }

    /**
     * @test
     */
    public function shouldSuccessProcessWithCustomKeys(): void
    {
        $containerBuilder = $this->createContainerBuilderWithDefinitionsBuilder();

        $checkContainerDefinition1 = new Definition(StubCheck::class);
        $checkContainerDefinition1->addTag('diagnostic.check', [
            'key' => 'foo',
        ]);

        $checkContainerDefinition2 = new Definition(StubCheck::class);
        $checkContainerDefinition2->addTag('diagnostic.check', [
            'key' => 'bar',
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
                ],
            ],
            [
                'addCheck',
                [
                    'bar',
                    new Reference('check_2'),
                    '',
                ],
            ],
        ], $definitionsBuilder->getMethodCalls());
    }

    /**
     * @test
     */
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
                ],
            ],
            [
                'addCheck',
                [
                    'check_1',
                    new Reference('check_1'),
                    'bar',
                ],
            ],
        ], $definitionsBuilder->getMethodCalls());
    }

    /**
     * @test
     */
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
                ],
            ],
        ], $definitionsBuilder->getMethodCalls());
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * Create container builder with definitions builder
     *
     * @return ContainerBuilder
     */
    private function createContainerBuilderWithDefinitionsBuilder(): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setDefinition('diagnostic.definitions.builder', new Definition(DefinitionCollectionBuilder::class));

        return $containerBuilder;
    }
}
