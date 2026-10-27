<?php

declare(strict_types=1);

namespace Tests\Unit\Locator;

use Berry\Symfony\Locator\Trait\WithGetUserLocator;
use Override;
use SensitiveParameter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class WithGetUserLocatorImplementation
{
    use WithGetUserLocator;

    public function setTokenStorageLocator(?TokenStorageInterface $locator): void
    {
        $this->tokenStorageLocator = $locator;
    }

    public function testGetUser(): ?UserInterface
    {
        return $this->getUser();
    }
}

test('getUser uses injected locator', function () {
    $user = new class implements UserInterface {
        #[Override]
        public function getRoles(): array
        {
            return ['ROLE_USER'];
        }

        public function getPassword(): null
        {
            return null;
        }

        public function getSalt(): null
        {
            return null;
        }

        public function eraseCredentials(): void
        {
        }

        #[Override]
        public function getUserIdentifier(): string
        {
            return 'user';
        }
    };

    $token = new class implements TokenInterface {
        private ?UserInterface $user = null;
        /** @var array<array-key, mixed> */
        private array $attributes = [];

        #[Override]
        public function getRoleNames(): array
        {
            return [];
        }

        public function getCredentials(): mixed
        {
            return null;
        }

        #[Override]
        public function getUser(): ?UserInterface
        {
            return $this->user;
        }

        #[Override]
        public function setUser(UserInterface $user): void
        {
            $this->user = $user;
        }

        public function isAuthenticated(): bool
        {
            return true;
        }

        public function setAuthenticated(bool $_isAuthenticated): void
        {
        }

        public function eraseCredentials(): void
        {
        }

        /** @return array<array-key, mixed> */
        #[Override]
        public function getAttributes(): array
        {
            return $this->attributes;
        }

        /** @param array<array-key, mixed> $attributes */
        #[Override]
        public function setAttributes(array $attributes): void
        {
            $this->attributes = $attributes;
        }

        #[Override]
        public function hasAttribute(string $name): bool
        {
            return isset($this->attributes[$name]);
        }

        #[Override]
        public function getAttribute(string $name): mixed
        {
            return $this->attributes[$name] ?? null;
        }

        /** @param mixed $value */
        #[Override]
        public function setAttribute(string $name, $value): void
        {
            $this->attributes[$name] = $value;
        }

        #[Override]
        public function getUserIdentifier(): string
        {
            return 'user';
        }

        #[Override]
        public function __toString(): string
        {
            return 'token';
        }

        /** @return array<array-key, mixed> */
        #[Override]
        public function __serialize(): array
        {
            return $this->attributes;
        }

        /** @param array<array-key, mixed> $data */
        #[Override]
        public function __unserialize(array $data): void
        {
            $this->attributes = $data;
        }
    };
    $token->setUser($user);

    $stub = new class implements TokenStorageInterface {
        private ?TokenInterface $token = null;

        #[Override]
        public function setToken(#[SensitiveParameter] ?TokenInterface $token): void
        {
            $this->token = $token;
        }

        #[Override]
        public function getToken(): ?TokenInterface
        {
            return $this->token;
        }
    };
    $stub->setToken($token);

    $impl = new WithGetUserLocatorImplementation();
    $impl->setTokenStorageLocator($stub);

    $result = $impl->testGetUser();

    expect($result)->toBe($user);
});
