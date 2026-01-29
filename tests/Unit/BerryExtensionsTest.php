<?php declare(strict_types=1);

use Symfony\Contracts\Translation\TranslatorInterface;

use function Berry\Html\div;
use function Berry\Html\h1;

test('Extension: toResponse', function () {
    $res = div()->text('Hello World!')->toResponse();

    expect($res->getStatusCode())->toBe(200);
    expect($res->getContent())->toBe('<div>Hello World!</div>');

    $res = h1()->text('Page not found!')->toResponse(404, ['X-Rendered-By' => 'berry']);

    expect($res->getStatusCode())->toBe(404);
    expect($res->headers->get('X-Rendered-By'))->toBe('berry');
    expect($res->getContent())->toBe('<h1>Page not found!</h1>');
});

test('Extension: trans', function () {
    $translator = new class implements TranslatorInterface {
        /**
         * @param array<string, mixed> $parameters
         */
        public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
        {
            if ($id === 'some.key') {
                return 'Hello, World!';
            }

            return '???';
        }

        public function getLocale(): string
        {
            return 'en';
        }
    };

    expect(div()->trans('some.key', translator: $translator)->toString())->toBe('<div>Hello, World!</div>');
});

test('Extension: dump', function () {
    expect(div()->text('Hello, World!')->dump('swaggy')->toString())->toContain('sf-dump');
});
