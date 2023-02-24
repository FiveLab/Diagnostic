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

namespace FiveLab\Component\Diagnostic\DependencyInjection;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\LazyContainerCheck;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The compiler pass for add diagnostic check to builder by tag.
 */
class AddDiagnosticToBuilderCheckPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private string $builderServiceName;

    /**
     * @var string
     */
    private string $checkTagName;

    /**
     * @var bool
     */
    private bool $useLazyDecorator;

    /**
     * Constructor.
     *
     * @param string $builderServiceName
     * @param string $checkTagName
     * @param bool   $useLazyDecorator
     */
    public function __construct(string $builderServiceName = 'diagnostic.definitions.builder', string $checkTagName = 'diagnostic.check', bool $useLazyDecorator = false)
    {
        $this->builderServiceName = $builderServiceName;
        $this->checkTagName = $checkTagName;
        $this->useLazyDecorator = $useLazyDecorator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        try {
            $builderDefinition = $container->findDefinition($this->builderServiceName);
        } catch (InvalidArgumentException $e) {
            return;
        }

        foreach ($container->findTaggedServiceIds($this->checkTagName) as $serviceId => $tags) {
            $serviceDefinition = $container->getDefinition($serviceId);
            $class = $serviceDefinition->getClass();

            try {
                $class = $container->getParameterBag()->resolveValue($class);

                if (!\class_exists($class)) {
                    throw new \RuntimeException(\sprintf(
                        'The check class "%s" does not exist.',
                        $class
                    ));
                }

                if (!\is_a($class, CheckInterface::class, true)) {
                    throw new \RuntimeException(\sprintf(
                        'The check "%s" should implement "%s" interface.',
                        $class,
                        CheckInterface::class
                    ));
                }
            } catch (\Throwable $e) {
                throw new \RuntimeException(\sprintf(
                    'Cannot compile diagnostic check with service id "%s".',
                    $serviceId
                ), 0, $e);
            }

            foreach ($tags as $attributes) {
                $checkServiceId = $serviceId;

                if ($this->useLazyDecorator) {
                    // Must use "lazy decorator" for check instances.
                    // Set public flag for get check from container.
                    $serviceDefinition->setPublic(true);

                    $lazyServiceId = \sprintf('%s.lazy', $serviceId);
                    $lazyServiceDef = (new Definition(LazyContainerCheck::class))
                        ->setArguments([
                            new Reference('service_container'),
                            $serviceId,
                        ]);

                    $container->setDefinition($lazyServiceId, $lazyServiceDef);

                    $checkServiceId = $lazyServiceId;
                }

                $builderDefinition->addMethodCall('addCheck', [
                    $attributes['key'] ?? $serviceId,
                    new Reference($checkServiceId),
                    $attributes['group'] ?? '',
                ]);
            }
        }
    }
}
