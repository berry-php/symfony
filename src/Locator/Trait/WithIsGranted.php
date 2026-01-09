<?php declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Symfony\Locator\ComponentServiceLocator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use LogicException;

trait WithIsGranted
{
    protected ?AuthorizationCheckerInterface $authorizationChecker = null;

    /**
     * Checks if the attribute is granted against the current authentication token and optionally supplied subject.
     *
     * @throws LogicException
     */
    protected function isGranted(mixed $attribute, mixed $subject = null): bool
    {
        return ($this->authorizationChecker ?? ComponentServiceLocator::getAuthorizationChecker())->isGranted($attribute, $subject);
    }
}
