<?php declare(strict_types=1);

namespace Berry\Symfony\View;

use Berry\Symfony\Locator\Trait\WithGenerateUrl;
use Berry\Symfony\Locator\Trait\WithGetUser;
use Berry\Symfony\Locator\Trait\WithIsGranted;
use Berry\Symfony\Locator\Trait\WithRenderIcon;
use Berry\Symfony\Locator\Trait\WithTranslate;
use Deprecated;

/**
 * @deprecated No longer necessary, please use the associated service locator traits
 */
class AbstractViewFactory
{
    use WithGenerateUrl;
    use WithGetUser;
    use WithTranslate;
    use WithRenderIcon;
    use WithIsGranted;
}
