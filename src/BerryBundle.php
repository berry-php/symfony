<?php declare(strict_types=1);

namespace Berry\Symfony;

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

        $enabledBundles = $builder->getParameter('kernel.bundles');

        if (isset($enabledBundles['UXIconsBundle'])) {
            $services->set(IconFactory::class);
            $services->alias(IconFactoryInterface::class, IconFactory::class);
        }
    }
}
