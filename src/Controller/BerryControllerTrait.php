<?php declare(strict_types=1);

namespace Berry\Symfony\Controller;

use Berry\Element;
use Symfony\Component\HttpFoundation\Response;

trait BerryControllerTrait
{
    /**
     * Renders a view.
     *
     * @param array<string, string> $headers
     */
    protected function renderBerryView(Element $element, int $statusCode = 200, array $headers = []): Response
    {
        return new Response(
            $element->toString(),
            $statusCode,
            $headers,
        );
    }
}
