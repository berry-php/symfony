<?php declare(strict_types=1);

namespace Tests\Unit\Locator;

use Berry\Symfony\Locator\Trait\WithCreateCsrfTokenLocator;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class WithCreateCsrfTokenLocatorImplementation
{
    use WithCreateCsrfTokenLocator;

    public function setCsrfTokenLocator(?CsrfTokenManagerInterface $locator): void
    {
        $this->csrfTokenManagerLocator = $locator;
    }

    public function testCreateCsrfToken(string $tokenId): CsrfToken
    {
        return $this->createCsrfToken($tokenId);
    }
}

test('createCsrfToken uses injected locator', function () {
    $stub = new class implements CsrfTokenManagerInterface {
        public function getToken(string $tokenId): CsrfToken { return new CsrfToken($tokenId, 'value'); }
        public function refreshToken(string $tokenId): CsrfToken { throw new \LogicException; }
        public function removeToken(string $tokenId): ?string { return null; }
        public function tokenExists(string $tokenId): bool { return true; }
        public function isTokenValid(CsrfToken $token): bool { return true; }
    };

    $impl = new WithCreateCsrfTokenLocatorImplementation();
    $impl->setCsrfTokenLocator($stub);

    $token = $impl->testCreateCsrfToken('test');

    expect($token->getId())->toBe('test');
});
