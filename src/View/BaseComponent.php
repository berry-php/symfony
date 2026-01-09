<?php declare(strict_types=1);

namespace Berry\Symfony\View;

use Berry\Symfony\Locator\Trait\WithGenerateUrlLocator;
use Berry\Symfony\Locator\Trait\WithGetUserLocator;
use Berry\Symfony\Locator\Trait\WithIsGrantedLocator;
use Berry\Symfony\Locator\Trait\WithRenderIconLocator;
use Berry\Symfony\Locator\Trait\WithTranslatorLocator;
use Berry\Component;

abstract class BaseComponent extends Component
{
    use WithGenerateUrlLocator;
    use WithGetUserLocator;
    use WithTranslatorLocator;
    use WithRenderIconLocator;
    use WithIsGrantedLocator;
}
