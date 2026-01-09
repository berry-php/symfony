<?php declare(strict_types=1);

namespace Berry\Symfony;

use Berry\Symfony\Locator\ComponentServiceLocator;
use Berry\Symfony\UX\IconFactory;
use Berry\Symfony\UX\IconFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

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
    }

    public function boot(): void
    {
        if ($this->container?->has(ComponentServiceLocator::class)) {
            $this->container->get(ComponentServiceLocator::class);
        }
    }
}
