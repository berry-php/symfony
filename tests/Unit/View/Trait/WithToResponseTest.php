<?php declare(strict_types=1);

use Berry\Symfony\View\Trait\WithToResponse;

class TestComponentWithToResponse
{
    use WithToResponse;

    public function __construct(
        private string $content = ''
    ) {}

    public function toString(): string
    {
        return '<div>' . $this->content . '</div>';
    }
}

test('returns response with default values', function () {
    $component = new TestComponentWithToResponse('Content');

    $response = $component->toResponse();

    expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('<div>Content</div>');
});

test('returns response with custom status code', function () {
    $component = new TestComponentWithToResponse('Error');

    $response = $component->toResponse(404);

    expect($response->getStatusCode())->toBe(404);
});

test('returns response with custom headers', function () {
    $component = new TestComponentWithToResponse('Data');

    $headers = ['X-Cache' => 'HIT', 'X-Version' => '1.0'];
    $response = $component->toResponse(200, $headers);

    expect($response->headers->get('X-Cache'))->toBe('HIT');
    expect($response->headers->get('X-Version'))->toBe('1.0');
});
