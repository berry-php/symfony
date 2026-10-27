<?php

declare(strict_types=1);

namespace Berry\Symfony;

use Closure;
use Berry\Symfony\DependencyInjection\Compiler\AsTwigViewPass;
use Berry\Symfony\ErrorRenderer\AbstractErrorPage;
use Berry\Symfony\ErrorRenderer\BerryErrorRenderer;
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
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Twig\Extension\AbstractExtension;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
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

    #[Override]
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
            ->children()
            ->scalarNode('error_page')
            ->info('AbstractErrorPage subclass used to render HTML error pages')
            ->defaultNull()
            ->validate()
            ->ifTrue(
                static fn(mixed $class): bool => (
                    $class !== null && (!is_string($class) || !is_a($class, AbstractErrorPage::class, true))
                ),
            )
            ->thenInvalid('%s must be a class extending ' . AbstractErrorPage::class)
            ->end()
            ->end()
            ->end();
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

        if (is_string($config['error_page'])) {
            $services
                ->set(BerryErrorRenderer::class)
                // -1 so TwigErrorRenderer (if installed) keeps its required HtmlErrorRenderer as inner service
                ->decorate('error_renderer.html', null, -1)
                ->args([
                    service('.inner'),
                    $config['error_page'],
                    inline_service(Closure::class)
                        ->factory([HtmlErrorRenderer::class, 'isDebug'])
                        ->args([service('request_stack'), param('kernel.debug')]),
                    param('kernel.charset'),
                ]);
        }

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
