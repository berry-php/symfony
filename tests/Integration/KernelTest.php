<?php

declare(strict_types=1);

use Berry\Symfony\Locator\AbstractServiceLocator;
use Berry\Symfony\BerryBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

beforeAll(function () {
    if (!class_exists(Kernel::class)) {
        throw new \RuntimeException('Symfony Kernel not available');
    }
});

test('bundle boots every autoconfigured locator', function () {
    $builder = new ContainerBuilder();
    $builder->setParameter('kernel.bundles', []);
    $builder->setParameter('kernel.environment', 'test');
    $builder->setParameter('kernel.build_dir', sys_get_temp_dir());
    $builder->setParameter('kernel.debug', false);

    $bundle = new BerryBundle();
    $extension = $bundle->getContainerExtension();
    assert($extension !== null);
    $builder->registerExtension($extension);
    $builder->loadFromExtension('berry');
    $bundle->build($builder);

    $builder->registerForAutoconfiguration(ServiceSubscriberInterface::class)->addTag('container.service_subscriber');

    $builder->register(BootTestLocator::class, BootTestLocator::class)->setAutoconfigured(true)->setAutowired(true);
    $builder->register(stdClass::class, stdClass::class)->setPublic(false);

    // registered only under an id, without a class alias
    $builder->register('boot.test', ArrayObject::class)->setPublic(false);
    $builder->compile();

    $bundle->setContainer($builder);
    $bundle->boot();

    expect(BootTestLocator::get(stdClass::class))->toBeInstanceOf(stdClass::class);
    expect(BootTestLocator::get('boot.test'))->toBeInstanceOf(ArrayObject::class);
});

class BootTestLocator extends AbstractServiceLocator
{
    #[Override]
    public static function services(): array
    {
        return [stdClass::class, 'boot.test' => ArrayObject::class];
    }

    public static function get(string $id): mixed
    {
        return self::getService($id, 'none');
    }
}
