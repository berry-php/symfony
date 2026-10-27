<?php

declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Symfony\Locator\ComponentServiceLocator;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

trait WithParameterLocator
{
    protected ?ContainerBagInterface $parameterBagLocator = null;

    protected function getParameter(string $name): mixed
    {
        return ($this->parameterBagLocator ?? ComponentServiceLocator::getParameterBag())->get($name);
    }
}
