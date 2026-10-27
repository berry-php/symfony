<?php

declare(strict_types=1);

namespace Tests\Unit\ErrorRenderer;

use Berry\Element;
use Berry\Symfony\ErrorRenderer\AbstractErrorPage;
use Berry\Symfony\ErrorRenderer\BerryErrorRenderer;
use Override;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Berry\Html\h1;

class TestErrorPage extends AbstractErrorPage
{
    #[Override]
    public function renderComponent(): Element
    {
        return h1()->text('Error ' . $this->exception->getStatusCode());
    }
}

test('renders the component with the status code and an html content type', function () {
    $renderer = new BerryErrorRenderer(new HtmlErrorRenderer(), TestErrorPage::class, static fn(): bool => false);

    $exception = $renderer->render(new NotFoundHttpException());

    expect($exception->getAsString())->toBe('<h1>Error 404</h1>');
    expect($exception->getHeaders())->toBe(['Content-Type' => 'text/html; charset=UTF-8']);
});
