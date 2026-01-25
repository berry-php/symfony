<?php declare(strict_types=1);

use Berry\Symfony\Controller\BerryControllerTrait;
use Symfony\Component\HttpFoundation\Response;

use function Berry\Html\button;

$testController = new class {
    use BerryControllerTrait;
};

test('renderBerryView returns a Symfony Response with rendered content', function () use ($testController) {
    $refl = new ReflectionMethod($testController, 'renderBerryView');

    /** @var Response */
    $response = $refl->invoke($testController, button()->class('btn btn-primary')->text('Hello, World!'), 201, ['X-Custom' => 'Yes']);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(201);
    expect($response->headers->get('X-Custom'))->toBe('Yes');
    expect($response->getContent())->toBe('<button class="btn btn-primary">Hello, World!</button>');
});
