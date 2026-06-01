<?php declare(strict_types=1);

namespace Tests\Unit\Locator;

use Berry\Symfony\Locator\Trait\WithAssetLocator;
use Symfony\Component\Asset\Packages;

class WithAssetLocatorImplementation
{
    use WithAssetLocator;

    public function setAssetPackagesLocator(?Packages $locator): void
    {
        $this->assetPackagesLocator = $locator;
    }

    public function testAsset(string $path, ?string $packageName = null): string
    {
        return $this->asset($path, $packageName);
    }
}

test('asset uses injected locator', function () {
    $stub = new class extends Packages {
        public function getUrl(string $path, ?string $packageName = null): string
        {
            return sprintf('/assets/%s/%s', $packageName ?? 'default', $path);
        }
    };

    $impl = new WithAssetLocatorImplementation();
    $impl->setAssetPackagesLocator($stub);

    expect($impl->testAsset('css/main.css'))->toBe('/assets/default/css/main.css');
    expect($impl->testAsset('css/main.css', 'app'))->toBe('/assets/app/css/main.css');
});
