<?php declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Symfony\Locator\ComponentServiceLocator;
use Symfony\Component\Asset\Packages;

trait WithAssetLocator
{
    protected ?Packages $assetPackagesLocator = null;

    protected function asset(string $path, ?string $packageName = null): string
    {
        return ($this->assetPackagesLocator ?? ComponentServiceLocator::getAssetPackages())->getUrl($path, $packageName);
    }
}
