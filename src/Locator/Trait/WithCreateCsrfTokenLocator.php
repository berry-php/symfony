<?php declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Symfony\Locator\ComponentServiceLocator;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

trait WithCreateCsrfTokenLocator
{
    protected ?CsrfTokenManagerInterface $csrfTokenManagerLocator = null;

    protected function createCsrfToken(string $tokenId): CsrfToken
    {
        return ($this->csrfTokenManagerLocator ?? ComponentServiceLocator::getCsrfTokenManager())->getToken($tokenId);
    }
}
