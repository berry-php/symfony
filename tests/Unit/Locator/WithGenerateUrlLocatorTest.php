<?php declare(strict_types=1);

namespace Tests\Unit\Locator;

use Berry\Symfony\Locator\Trait\WithGenerateUrlLocator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class WithGenerateUrlLocatorImplementation
{
    use WithGenerateUrlLocator;

    public function setRouterLocator(?RouterInterface $locator): void
    {
        $this->routerLocator = $locator;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function testGenerateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->generateUrl($route, $parameters, $referenceType);
    }
}

test('generateUrl uses injected locator', function () {
    $stub = new class implements RouterInterface {
        /**
         * @param array<string, mixed> $parameters
         */
        public function generate(string $name, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
        {
            return '/generated/' . $name;
        }
        public function setContext(\Symfony\Component\Routing\RequestContext $context): void {}
        public function getContext(): \Symfony\Component\Routing\RequestContext { return new \Symfony\Component\Routing\RequestContext(); }
        public function getRouteCollection(): \Symfony\Component\Routing\RouteCollection { return new \Symfony\Component\Routing\RouteCollection(); }
        /** @return array<string, mixed> */
        public function match(string $pathinfo): array { return ['_route' => 'default']; }
    };

    $impl = new WithGenerateUrlLocatorImplementation();
    $impl->setRouterLocator($stub);

    $url = $impl->testGenerateUrl('test_route');

    expect($url)->toBe('/generated/test_route');
});
