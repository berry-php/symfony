<?php declare(strict_types=1);

namespace Berry\Symfony\View;

use Berry\Symfony\Locator\Trait\WithCreateCsrfTokenLocator;
use Berry\Symfony\Locator\Trait\WithGenerateUrlLocator;
use Berry\Symfony\Locator\Trait\WithGetUserLocator;
use Berry\Symfony\Locator\Trait\WithIsGrantedLocator;
use Berry\Symfony\Locator\Trait\WithRenderIconLocator;
use Berry\Symfony\Locator\Trait\WithTranslatorLocator;
use Berry\Symfony\Locator\Trait\WithTwigRendering;
use Berry\Component;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseComponent extends Component
{
    use WithCreateCsrfTokenLocator;
    use WithGenerateUrlLocator;
    use WithGetUserLocator;
    use WithIsGrantedLocator;
    use WithRenderIconLocator;
    use WithTranslatorLocator;
    use WithTwigRendering;

    /**
     * @param array<string, mixed> $headers
     */
    public function toResponse(int $status = 200, array $headers = []): Response
    {
        return new Response($this->toString(), $status, $headers);
    }
}
