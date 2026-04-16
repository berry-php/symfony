<?php declare(strict_types=1);

namespace Berry\Symfony\Form;

use Berry\Element;
use Symfony\Component\Form\FormView;

interface FormElementInterface
{
    public function priority(): int;

    public function supports(FormView $view): bool;

    /**
     * @param array<string, mixed> $variables
     */
    public function render(FormView $view, FormRendererInterface $formRenderer, array $variables = []): ?Element;
}
