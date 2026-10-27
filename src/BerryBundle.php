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
use Berry\Symfony\Locator\AbstractServiceLocator;
use Berry\Symfony\Locator\ComponentServiceLocator;
use Berry\Symfony\Twig\TwigViewExtension;
use Berry\Symfony\UX\IconFactory;
use Berry\Symfony\UX\IconFactoryInterface;
use Berry\Symfony\View\Attribute\AsTwigView;
use Override;
use ReflectionClass;
use ReflectionNamedType;
use Reflector;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Twig\Extension\AbstractExtension;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

class BerryBundle extends AbstractBundle
{
    private const string LOCATORS_SERVICE = 'berry.service_locators';

    #[Override]
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

        $container->registerForAutoconfiguration(AbstractServiceLocator::class)->addTag(AbstractServiceLocator::TAG);

        $container->addCompilerPass(new AsTwigViewPass());
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[Override]
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $services = $configurator->services()->defaults()->autowire()->autoconfigure();

        $services
            ->set(ComponentServiceLocator::class)
            ->tag('container.service_subscriber');

        $services
            ->set(self::LOCATORS_SERVICE, ServiceLocator::class)
            ->public()
            ->args([tagged_iterator(AbstractServiceLocator::TAG)])
            ->tag('container.service_locator');

        if (class_exists(AbstractExtension::class)) {
            $services
                ->set(TwigViewExtension::class)
                ->tag('twig.extension');
        }

        $enabledBundles = $container->getParameter('kernel.bundles');
        assert(is_array($enabledBundles));

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

    #[Override]
    public function boot(): void
    {
        if (!$this->container?->has(self::LOCATORS_SERVICE)) {
            return;
        }

        $locators = $this->container->get(self::LOCATORS_SERVICE);
        assert($locators instanceof ServiceLocator);

        foreach ($locators->getProvidedServices() as $id => $_) {
            $locators->get((string) $id);
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
