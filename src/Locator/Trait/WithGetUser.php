<?php declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Symfony\Locator\ComponentServiceLocator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use LogicException;

trait WithGetUser
{
    protected ?TokenStorageInterface $tokenStorage = null;

    /**
     * Get a user from the Security Token Storage.
     *
     * @throws LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser(): ?UserInterface
    {
        $tokenStorage = $this->tokenStorage ?? ComponentServiceLocator::getTokenStorage();

        $token = $tokenStorage->getToken();

        if ($token === null) {
            return null;
        }

        return $token->getUser();
    }
}
