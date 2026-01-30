<?php declare(strict_types=1);

namespace Tests\Unit\Locator;

use Berry\Symfony\Locator\Trait\WithGetTokenLocator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class WithGetTokenLocatorImplementation
{
    use WithGetTokenLocator;

    public function setTokenStorageLocator(?TokenStorageInterface $locator): void
    {
        $this->tokenStorageLocator = $locator;
    }

    public function testGetToken(): ?TokenInterface
    {
        return $this->getToken();
    }
}

test('getToken uses injected locator', function () {
    $token = new class implements TokenInterface {
        /** @var array<string, mixed> */
        private array $attributes = [];

        public function getRoleNames(): array { return []; }
        public function getCredentials(): mixed { return null; }
        public function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface { return null; }
        public function setUser(\Symfony\Component\Security\Core\User\UserInterface $user): void {}
        public function isAuthenticated(): bool { return true; }
        public function setAuthenticated(bool $isAuthenticated): void {}
        public function eraseCredentials(): void {}
        /** @return array<string, mixed> */
        public function getAttributes(): array { return $this->attributes; }
        /** @param array<string, mixed> $attributes */
        public function setAttributes(array $attributes): void { $this->attributes = $attributes; }
        public function hasAttribute(string $name): bool { return isset($this->attributes[$name]); }
        public function getAttribute(string $name): mixed { return $this->attributes[$name] ?? null; }
        /** @param mixed $value */
        public function setAttribute(string $name, $value): void { $this->attributes[$name] = $value; }
        public function getUserIdentifier(): string { return 'user'; }
        public function __toString(): string { return 'token'; }
        /** @return array<string, mixed> */
        public function __serialize(): array { return $this->attributes; }
        /** @param array<string, mixed> $data */
        public function __unserialize(array $data): void { $this->attributes = $data; }
    };

    $stub = new class implements TokenStorageInterface {
        private ?TokenInterface $token = null;
        public function setToken(?TokenInterface $token): void { $this->token = $token; }
        public function getToken(): ?TokenInterface { return $this->token; }
    };
    $stub->setToken($token);

    $impl = new WithGetTokenLocatorImplementation();
    $impl->setTokenStorageLocator($stub);

    $result = $impl->testGetToken();

    expect($result)->toBe($token);
});
