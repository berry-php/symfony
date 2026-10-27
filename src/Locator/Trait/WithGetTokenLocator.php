<?php

declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Symfony\Locator\ComponentServiceLocator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use LogicException;

trait WithGetTokenLocator
{
    protected ?TokenStorageInterface $tokenStorageLocator = null;

    /**
     * Get a token from the Security Token Storage.
     *
     * @throws LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getToken()
     */
    protected function getToken(): ?TokenInterface
    {
        $tokenStorage = $this->tokenStorageLocator ?? ComponentServiceLocator::getTokenStorage();

        return $tokenStorage->getToken();
    }
}
