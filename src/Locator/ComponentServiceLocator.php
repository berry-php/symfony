<?php declare(strict_types=1);

namespace Berry\Symfony\Locator;

use Berry\Symfony\UX\IconFactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use LogicException;

class ComponentServiceLocator implements ServiceSubscriberInterface
{
    protected static ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        static::$container = $container;
    }

    public static function getSubscribedServices(): array
    {
        return [
            AuthorizationCheckerInterface::class => '?' . AuthorizationCheckerInterface::class,
            CsrfTokenManagerInterface::class => '?' . CsrfTokenManagerInterface::class,
            IconFactoryInterface::class => '?' . IconFactoryInterface::class,
            RouterInterface::class => '?' . RouterInterface::class,
            TokenStorageInterface::class => '?' . TokenStorageInterface::class,
            TranslatorInterface::class => '?' . TranslatorInterface::class,
        ];
    }

    public static function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        /** @var AuthorizationCheckerInterface */
        return self::getService(AuthorizationCheckerInterface::class, 'symfony/security-bundle');
    }

    public static function getCsrfTokenManager(): CsrfTokenManagerInterface
    {
        /** @var CsrfTokenManagerInterface */
        return self::getService(CsrfTokenManagerInterface::class, 'symfony/security-bundle');
    }

    public static function getIconFactory(): IconFactoryInterface
    {
        /** @var IconFactoryInterface */
        return self::getService(IconFactoryInterface::class, 'symfony/ux-icons');
    }

    public static function getRouter(): RouterInterface
    {
        /** @var RouterInterface */
        return self::getService(RouterInterface::class, 'symfony/routing');
    }

    public static function getTokenStorage(): TokenStorageInterface
    {
        /** @var TokenStorageInterface */
        return self::getService(TokenStorageInterface::class, 'symfony/security-bundle');
    }

    public static function getTranslator(): TranslatorInterface
    {
        /** @var TranslatorInterface */
        return self::getService(TranslatorInterface::class, 'symfony/translation');
    }

    protected static function getService(string $id, string $packageName): mixed
    {
        if (!static::$container->has($id)) {
            throw new LogicException(sprintf(
                'The service "%s" is not available. Try running "composer require %s".',
                $id,
                $packageName
            ));
        }

        return static::$container->get($id);
    }
}
