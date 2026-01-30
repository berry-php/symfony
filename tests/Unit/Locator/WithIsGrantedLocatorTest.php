<?php declare(strict_types=1);

namespace Tests\Unit\Locator;

use Berry\Symfony\Locator\Trait\WithIsGrantedLocator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class WithIsGrantedLocatorImplementation
{
    use WithIsGrantedLocator;

    public function setAuthorizationCheckerLocator(?AuthorizationCheckerInterface $locator): void
    {
        $this->authorizationCheckerLocator = $locator;
    }

    public function testIsGranted(mixed $attribute, mixed $subject = null): bool
    {
        return $this->isGranted($attribute, $subject);
    }
}

test('isGranted uses injected locator', function () {
    $stub = new class implements AuthorizationCheckerInterface {
        public function isGranted(mixed $attribute, mixed $subject = null, ?\Symfony\Component\Security\Core\Authorization\AccessDecision $accessDecision = null): bool
        {
            return true;
        }
    };

    $impl = new WithIsGrantedLocatorImplementation();
    $impl->setAuthorizationCheckerLocator($stub);

    $result = $impl->testIsGranted('ROLE_USER');

    expect($result)->toBeTrue();
});
