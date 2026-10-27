<?php

declare(strict_types=1);

namespace Berry\Symfony\Locator;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\Attribute\SubscribedService;
use Override;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use LogicException;

abstract class AbstractServiceLocator implements ServiceSubscriberInterface
{
    public const string TAG = 'berry.service_locator';

    /**
     * @var array<class-string, ContainerInterface>
     */
    private static array $containers = [];

    public function __construct(ContainerInterface $container)
    {
        self::$containers[static::class] = $container;
    }

    /**
     * @return array<array-key, class-string>
     */
    abstract public static function services(): array;

    #[Override]
    public static function getSubscribedServices(): array
    {
        $services = [];

        foreach (static::services() as $id => $class) {
            // a string key alone is only a name, Symfony would still resolve by type
            $services[] = is_int($id)
                ? '?' . $class
                : new SubscribedService($id, $class, true, new Autowire(service: $id));
        }

        return $services;
    }

    protected static function getService(string $id, string $packageName): mixed
    {
        $container = self::$containers[static::class] ?? throw new LogicException(sprintf(
            'The service locator "%s" has not been booted yet. Is it registered as a service?',
            static::class,
        ));

        if (!$container->has($id)) {
            throw new LogicException(sprintf(
                'The service "%s" is not available. Try running "composer require %s".',
                $id,
                $packageName,
            ));
        }

        return $container->get($id);
    }
}
