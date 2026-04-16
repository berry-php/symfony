<?php declare(strict_types=1);

use Berry\Symfony\Form\FormElementInterface;
use Berry\Symfony\Form\FormElementRegistry;
use Berry\Symfony\Form\FormRendererInterface;
use Berry\Symfony\Test\Unit\Form\Support\FormTestSupport;
use Symfony\Component\Form\FormView;

test('FormElementRegistry returns null when no element supports the view', function () {
    $registry = new FormElementRegistry([
        new class implements FormElementInterface {
            public function priority(): int { return 10; }
            public function supports(FormView $view): bool { return false; }
            public function render(FormView $view, FormRendererInterface $formRenderer, array $variables = []): ?\Berry\Element { return null; }
        },
    ]);

    expect($registry->resolve(FormTestSupport::field('email')))->toBeNull();
});

test('FormElementRegistry prefers higher priority supporting elements', function () {
    $low = new class implements FormElementInterface {
        public function priority(): int { return 10; }
        public function supports(FormView $view): bool { return true; }
        public function render(FormView $view, FormRendererInterface $formRenderer, array $variables = []): ?\Berry\Element { return null; }
    };

    $high = new class implements FormElementInterface {
        public function priority(): int { return 100; }
        public function supports(FormView $view): bool { return true; }
        public function render(FormView $view, FormRendererInterface $formRenderer, array $variables = []): ?\Berry\Element { return null; }
    };

    $registry = new FormElementRegistry([$low, $high]);

    expect($registry->resolve(FormTestSupport::field('email')))->toBe($high);
});
