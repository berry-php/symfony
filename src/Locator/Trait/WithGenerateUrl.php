<?php declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Symfony\Locator\ComponentServiceLocator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

trait WithGenerateUrl
{
    protected ?RouterInterface $router = null;

    /**
     * Generates a URL from the given parameters.
     *
     * @param array<string, mixed> $parameters
     *
     * @see UrlGeneratorInterface
     */
    protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return ($this->router ?? ComponentServiceLocator::getRouter())->generate($route, $parameters, $referenceType);
    }
}
