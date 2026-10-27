<?php

declare(strict_types=1);

namespace Berry\Symfony;

use Berry\Symfony\DependencyInjection\Compiler\AsTwigViewPass;
use Berry\Symfony\Form\Elements\ButtonFormElement;
use Berry\Symfony\Form\Elements\CheckboxFormElement;
use Berry\Symfony\Form\Elements\ChoiceFormElement;
use Berry\Symfony\Form\Elements\RadioFormElement;
use Berry\Symfony\Form\Elements\TextAreaFormElement;
use Berry\Symfony\Form\FormElementRegistry;
use Berry\Symfony\Form\FormRenderer;
use Berry\Symfony\Form\FormRendererInterface;
use Berry\Symfony\Locator\ComponentServiceLocator;
use Berry\Symfony\Twig\TwigViewExtension;
use Berry\Symfony\UX\IconFactory;
use Berry\Symfony\UX\IconFactoryInterface;
use Berry\Symfony\View\Attribute\AsTwigView;
use ReflectionClass;
use ReflectionNamedType;
use Reflector;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Twig\Extension\AbstractExtension;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

class BerryBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerAttributeForAutoconfiguration(AsTwigView::class, static function (
            ChildDefinition $definition,
            AsTwigView $attribute,
            Reflector $reflector,
        ): void {
            if ($reflector instanceof ReflectionClass && self::hasRequiredRuntimeConstructorParameter($reflector)) {
                $definition->setAutowired(false);
            }

            $definition->addTag(AsTwigViewPass::TAG, ['name' => $attribute->name]);
        });

        $container->addCompilerPass(new AsTwigViewPass());
    }

    /**
     * @param array<string, string> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services()->defaults()->autowire()->autoconfigure();

        $services
            ->set(ComponentServiceLocator::class)
            ->public()
            ->tag('container.service_subscriber');

        if (class_exists(AbstractExtension::class)) {
            $services
                ->set(TwigViewExtension::class)
                ->tag('twig.extension');
        }

        $enabledBundles = $builder->getParameter('kernel.bundles');

        if (isset($enabledBundles['UXIconsBundle'])) {
            $services->set(IconFactory::class);
            $services->alias(IconFactoryInterface::class, IconFactory::class);
        }

        if (interface_exists(FormFactoryInterface::class)) {
            $services->set(FormRenderer::class);
            $services->alias(FormRendererInterface::class, FormRenderer::class);

            $services
                ->set(FormElementRegistry::class)
                ->args([tagged_iterator(FormElementRegistry::FORM_ELEMENT_TAG)]);

            $formElements = [
                ButtonFormElement::class,
                CheckboxFormElement::class,
                ChoiceFormElement::class,
                RadioFormElement::class,
                TextAreaFormElement::class,
            ];

            foreach ($formElements as $element) {
                $services->set($element)->tag(FormElementRegistry::FORM_ELEMENT_TAG);
            }
        }
    }

    public function boot(): void
    {
        if ($this->container?->has(ComponentServiceLocator::class)) {
            $this->container->get(ComponentServiceLocator::class);
        }
    }

    /**
     * @param ReflectionClass<object> $reflector
     */
    private static function hasRequiredRuntimeConstructorParameter(ReflectionClass $reflector): bool
    {
        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return false;
        }

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isOptional()) {
                continue;
            }

            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                return true;
            }
        }

        return false;
    }
}
