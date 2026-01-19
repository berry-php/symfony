<?php declare(strict_types=1);

namespace Berry\Symfony\Controller;

use Berry\Rendering\DirectOutputRenderer;
use Berry\Rendering\StringConcatRenderer;
use Berry\Rendering\StringRenderer;
use Berry\Element;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait BerryControllerTrait
{
    /**
     * Renders a view as a string
     */
    protected function renderBerryViewAsString(Element $element, ?StringRenderer $renderer = null): string
    {
        $renderer ??= new StringConcatRenderer();
        $element->render($renderer);
        return $renderer->renderToString();
    }

    /**
     * Renders a view.
     *
     * @param array<string, string> $headers
     */
    protected function renderBerryView(Element $element, int $statusCode = 200, array $headers = [], ?StringRenderer $renderer = null): Response
    {
        return new Response(
            $this->renderBerryViewAsString($element, $renderer),
            $statusCode,
            $headers,
        );
    }

    /**
     * Renders a streamed view to the client
     *
     * @param array<string, string> $headers
     */
    protected function renderStreamedBerryView(Element $element, int $statusCode = 200, array $headers = []): Response
    {
        return new StreamedResponse(
            function () use ($element) {
                $renderer = new DirectOutputRenderer();
                $element->render($renderer);

                flush();
            },
            $statusCode,
            array_merge(
                [
                    'X-Accel-Buffering' => 'no'
                ],
                $headers,
            )
        );
    }
}
