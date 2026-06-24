<?php declare(strict_types=1);

namespace Berry\Symfony\DependencyInjection\Compiler;

use Berry\Symfony\Twig\TwigViewExtension;
use Berry\Symfony\View\AbstractComponent;
use Berry\Symfony\View\Attribute\AsTwigView;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

final class AsTwigViewPass implements CompilerPassInterface
{
    public const TAG = 'berry.twig_view';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(TwigViewExtension::class)) {
            return;
        }

        /** @var array<string, class-string<AbstractComponent>> $views */
        $views = [];
        $services = [];

        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            $definition = $container->getDefinition($id);
            $class = $definition->getClass() ?? $id;

            if (!is_a($class, AbstractComponent::class, true)) {
                throw new LogicException(sprintf('Class "%s" used with #[%s] must extend %s.', $class, AsTwigView::class, AbstractComponent::class));
            }

            foreach ($tags as $tag) {
                $name = $this->tagName($tag, $class);

                if (isset($views[$name]) && $views[$name] !== $class) {
                    throw new LogicException(sprintf('Twig view "%s" is already registered for class "%s" and cannot also be registered for class "%s".', $name, $views[$name], $class));
                }

                $views[$name] = $class;
                $services += $this->constructorServices($class);
            }
        }

        $container->getDefinition(TwigViewExtension::class)->setArgument(0, $views);
        $container->getDefinition(TwigViewExtension::class)->setArgument(1, new ServiceLocatorArgument($services));
    }

    private function tagName(mixed $tag, string $class): string
    {
        if (!is_array($tag) || !isset($tag['name']) || !is_string($tag['name']) || $tag['name'] === '') {
            throw new LogicException(sprintf('Class "%s" used with #[%s] must define a non-empty name.', $class, AsTwigView::class));
        }

        return $tag['name'];
    }

    /**
     * @param class-string<AbstractComponent> $class
     * @return array<string, Reference>
     */
    private function constructorServices(string $class): array
    {
        $constructor = (new ReflectionClass($class))->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $services = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();
            $services[$typeName] = new Reference($typeName, ContainerInterface::IGNORE_ON_INVALID_REFERENCE);
        }

        return $services;
    }
}
