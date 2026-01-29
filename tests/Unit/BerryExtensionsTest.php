<?php declare(strict_types=1);

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

test('Test dumper', function () {
    expect(div()->text('Hello, World!')->dump('swaggy')->toString())->toContain('sf-dump');
});
