<?php declare(strict_types=1);

namespace Berry\Symfony\Form\Elements;

use Berry\Symfony\Form\Elements\AbstractFormElement;
use Berry\Symfony\Form\FormRendererInterface;
use Berry\Element;
use Symfony\Component\Form\FormView;

use function Berry\Html\textarea;

final class TextAreaFormElement extends AbstractFormElement
{
    public function supports(FormView $view): bool
    {
        return $this->hasBlockPrefix($view, 'textarea');
    }

    public function render(FormView $view, FormRendererInterface $formRenderer, array $variables = []): Element
    {
        $vars = $this->vars($view, $variables);

        return textarea()
            ->map(fn($textarea) => $this->applyWidgetAttributes($textarea, $view, $vars))
            ->text($this->arrayGetString($vars, 'value'));
    }
}
