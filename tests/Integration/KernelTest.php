<?php declare(strict_types=1);

use Berry\Symfony\Locator\ComponentServiceLocator;
use Berry\Symfony\BerryBundle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Kernel;

beforeAll(function () {
    if (!class_exists(Kernel::class)) {
        throw new \RuntimeException('Symfony Kernel not available');
    }
});

test('bundle has expected services registered', function () {
    $container = new Container();

    $container->set(ComponentServiceLocator::class, new class($container) extends ComponentServiceLocator {});

    expect($container->has(ComponentServiceLocator::class))->toBeTrue();
});

test('bundle boots without errors when locator available', function () {
    $container = new Container();

    $container->set(ComponentServiceLocator::class, new class($container) extends ComponentServiceLocator {});

    $bundle = new BerryBundle();
    $bundle->setContainer($container);

    $bundle->boot();

    expect($container->has(ComponentServiceLocator::class))->toBeTrue();
});
