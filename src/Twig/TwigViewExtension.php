<?php

declare(strict_types=1);

namespace Berry\Symfony\Twig;

use Berry\Symfony\View\AbstractComponent;
use Berry\Symfony\Twig\Node\TwigViewFunctionExpression;
use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigViewExtension extends AbstractExtension
{
    /**
     * @param array<string, class-string<AbstractComponent>> $views
     */
    public function __construct(
        private readonly array $views = [],
        private readonly ?ContainerInterface $services = null,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        $functions = [];

        foreach ($this->views as $name => $componentClass) {
            $functions[] = new TwigFunction(
                $name,
                fn(mixed ...$arguments): string => $this->render($componentClass, $arguments),
                ['is_safe' => ['html'], 'is_variadic' => true, 'node_class' => TwigViewFunctionExpression::class],
            );
        }

        return $functions;
    }

    /**
     * @param class-string<AbstractComponent> $componentClass
     * @param mixed[] $arguments
     */
    private function render(string $componentClass, array $arguments): string
    {
        return (new $componentClass(...$this->constructorArguments($componentClass, $arguments)))->toString();
    }

    /**
     * @param class-string<AbstractComponent> $componentClass
     * @param mixed[] $arguments
     * @return mixed[]
     */
    private function constructorArguments(string $componentClass, array $arguments): array
    {
        $constructor = (new ReflectionClass($componentClass))->getConstructor();

        $position = 0;
        $constructorArguments = [];

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $service = $this->serviceArgument($parameter);

            if ($service !== null) {
                $constructorArguments[] = $service;
                continue;
            }

            $name = $parameter->getName();

            if (array_key_exists($name, $arguments)) {
                if (array_key_exists($position, $arguments)) {
                    throw new LogicException(sprintf(
                        'Argument "$%s" is defined twice when rendering Twig view "%s".',
                        $name,
                        $componentClass,
                    ));
                }

                $constructorArguments[] = $arguments[$name];
                unset($arguments[$name]);
                continue;
            }

            if (array_key_exists($position, $arguments)) {
                $constructorArguments[] = $arguments[$position++];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $constructorArguments[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->getType()?->allowsNull() ?? true) {
                $constructorArguments[] = null;
                continue;
            }

            throw new LogicException(sprintf(
                'Missing argument "$%s" when rendering Twig view "%s".',
                $name,
                $componentClass,
            ));
        }

        // Preserve the existing behavior of ignoring unused positional arguments.
        foreach (array_keys($arguments) as $argumentName) {
            if (is_int($argumentName)) {
                continue;
            }

            throw new LogicException(sprintf(
                'Unknown argument "%s" when rendering Twig view "%s".',
                $argumentName,
                $componentClass,
            ));
        }

        return $constructorArguments;
    }

    private function serviceArgument(ReflectionParameter $parameter): ?object
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin() || $this->services === null) {
            return null;
        }

        $typeName = $type->getName();

        if (!$this->services->has($typeName)) {
            return null;
        }

        $service = $this->services->get($typeName);

        if (!is_object($service)) {
            throw new LogicException(sprintf(
                'Service "%s" used when rendering Twig views must be an object.',
                $typeName,
            ));
        }

        return $service;
    }
}
