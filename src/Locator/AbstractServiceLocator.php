<?php

declare(strict_types=1);

namespace Berry\Symfony\Locator;

use Psr\Container\ContainerInterface;
use Override;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use LogicException;

abstract class AbstractServiceLocator implements ServiceSubscriberInterface
{
    protected static ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        static::$container = $container;
    }

    /**
     * @return class-string[]
     */
    abstract public static function services(): array;

    #[Override]
    public static function getSubscribedServices(): array
    {
        return array_column(
            array_map(static fn(string $class) => ['key' => $class, 'value' => '?' . $class], static::services()),
            'value',
            'key',
        );
    }

    protected static function getService(string $id, string $packageName): mixed
    {
        if (!static::$container->has($id)) {
            throw new LogicException(sprintf(
                'The service "%s" is not available. Try running "composer require %s".',
                $id,
                $packageName,
            ));
        }

        return static::$container->get($id);
    }
}
