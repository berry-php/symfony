<?php declare(strict_types=1);

namespace Berry\Symfony;

use Berry\Symfony\Form\Elements\ButtonFormElement;
use Berry\Symfony\Form\Elements\CheckboxFormElement;
use Berry\Symfony\Form\Elements\ChoiceFormElement;
use Berry\Symfony\Form\Elements\RadioFormElement;
use Berry\Symfony\Form\Elements\TextAreaFormElement;
use Berry\Symfony\Form\FormElementRegistry;
use Berry\Symfony\Form\FormRenderer;
use Berry\Symfony\Form\FormRendererInterface;
use Berry\Symfony\Locator\ComponentServiceLocator;
use Berry\Symfony\UX\IconFactory;
use Berry\Symfony\UX\IconFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

class BerryBundle extends AbstractBundle
{
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
}
