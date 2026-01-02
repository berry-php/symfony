<?php declare(strict_types=1);

namespace Berry\Symfony\View;

use Berry\Symfony\UX\IconFactoryInterface;
use Berry\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use LogicException;

class AbstractViewFactory implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    #[Required]
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public static function getSubscribedServices(): array
    {
        return [
            'router' => '?' . RouterInterface::class,
            'security.token_storage' => '?' . TokenStorageInterface::class,
            'security.authorization_checker' => '?' . AuthorizationCheckerInterface::class,
            'ux.icon_factory' => '?' . IconFactoryInterface::class,
        ];
    }

    /**
     * Get a user from the Security Token Storage.
     *
     * @throws LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser(): ?UserInterface
    {
        if (!$this->container->has('security.token_storage')) {
            throw new LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }

        /** @var TokenStorageInterface */
        $tokenStorage = $this->container->get('security.token_storage');

        $token = $tokenStorage->getToken();

        if ($token === null) {
            return null;
        }

        return $token->getUser();
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param array<string, mixed> $parameters
     *
     * @see UrlGeneratorInterface
     */
    protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        /** @var RouterInterface */
        $router = $this->container->get('router');

        return $router->generate($route, $parameters, $referenceType);
    }

    /**
     * Checks if the attribute is granted against the current authentication token and optionally supplied subject.
     *
     * @throws LogicException
     */
    protected function isGranted(mixed $attribute, mixed $subject = null): bool
    {
        if (!$this->container->has('security.authorization_checker')) {
            throw new LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }

        /** @var AuthorizationCheckerInterface */
        $authChecker = $this->container->get('security.authorization_checker');

        return $authChecker->isGranted($attribute, $subject);
    }

    /**
     * @param array<string, string|bool> $attributes an array of HTML attributes
     *
     * @throws IconNotFoundException
     */
    protected function renderIcon(string $name, array $attributes = []): Element
    {
        if (!$this->container->has('ux.icon_factory')) {
            throw new LogicException('The UXIconsBundle is not registered in your application. Try running "composer require symfony/ux-icons".');
        }

        /** @var IconFactoryInterface */
        $iconFactory = $this->container->get('ux.icon_factory');

        return $iconFactory->render($name, $attributes);
    }
}
