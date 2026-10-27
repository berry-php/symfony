<?php

declare(strict_types=1);

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Berry\Html\HtmlTag;

use function Berry\Html\div;
use function Berry\Html\h1;

test('Extension: toResponse', function () {
    // @mago-expect analysis:non-documented-method
    /** @var Response $res */
    $res = div()->text('Hello World!')->toResponse();

    expect($res->getStatusCode())->toBe(200);
    expect($res->getContent())->toBe('<div>Hello World!</div>');

    // @mago-expect analysis:non-documented-method
    /** @var Response $res */
    $res = h1()->text('Page not found!')->toResponse(404, ['X-Rendered-By' => 'berry']);

    expect($res->getStatusCode())->toBe(404);
    expect($res->headers->get('X-Rendered-By'))->toBe('berry');
    expect($res->getContent())->toBe('<h1>Page not found!</h1>');
});

test('Extension: trans', function () {
    $translator = new class implements TranslatorInterface {
        /**
         * @param array<array-key, mixed> $parameters
         */
        #[Override]
        public function trans(
            string $id,
            array $parameters = [],
            ?string $domain = null,
            ?string $locale = null,
        ): string {
            if ($id === 'some.key') {
                return 'Hello, World!';
            }

            return '???';
        }

        #[Override]
        public function getLocale(): string
        {
            return 'en';
        }
    };

    // @mago-expect analysis:non-documented-method
    /** @var HtmlTag $node */
    $node = div()->trans('some.key', translator: $translator);
    expect($node->toString())->toBe('<div>Hello, World!</div>');
});

test('Extension: dump', function () {
    // @mago-expect analysis:non-documented-method
    /** @var HtmlTag $node */
    $node = div()->text('Hello, World!')->dump('swaggy');
    expect($node->toString())->toContain('sf-dump');
});
