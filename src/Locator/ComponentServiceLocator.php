<?php declare(strict_types=1);

namespace Berry\Symfony\Locator;

use Berry\Symfony\UX\IconFactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

class ComponentServiceLocator extends AbstractServiceLocator
{
    public static function services(): array
    {
        return [
            AuthorizationCheckerInterface::class,
            CsrfTokenManagerInterface::class,
            IconFactoryInterface::class,
            RouterInterface::class,
            TokenStorageInterface::class,
            TranslatorInterface::class,
            TwigEnvironment::class,
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

    public static function getTwigEnvironment(): TwigEnvironment
    {
        /** @var TwigEnvironment */
        return self::getService(TwigEnvironment::class, 'twig/twig');
    }
}
