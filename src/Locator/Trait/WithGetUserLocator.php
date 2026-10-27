<?php

declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Symfony\Component\Security\Core\User\UserInterface;
use LogicException;

trait WithGetUserLocator
{
    use WithGetTokenLocator;

    /**
     * Get a user from the Security Token Storage.
     *
     * @throws LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser(): ?UserInterface
    {
        $token = $this->getToken();

        if ($token === null) {
            return null;
        }

        return $token->getUser();
    }
}
