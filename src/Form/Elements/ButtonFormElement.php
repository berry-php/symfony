<?php declare(strict_types=1);

namespace Berry\Symfony\Form\Elements;

use Berry\Html\Enums\ButtonType;
use Berry\Symfony\Form\FormRendererInterface;
use Berry\Element;
use Symfony\Component\Form\FormView;

use function Berry\Html\button;
use function Berry\text;
use function Berry\unsafeRawText;

final class ButtonFormElement extends AbstractFormElement
{
    public function supports(FormView $view): bool
    {
        return $this->hasBlockPrefix($view, ['button', 'submit', 'reset']);
    }

    public function render(FormView $view, FormRendererInterface $formRenderer, array $variables = []): Element
    {
        $vars = $this->vars($view, $variables);
        $type = $vars['type'] ?? null;

        if (!is_string($type) || $type === '') {
            $type = match (true) {
                $this->hasBlockPrefix($view, 'submit') => ButtonType::Submit->value,
                $this->hasBlockPrefix($view, 'reset') => ButtonType::Reset->value,
                default => ButtonType::Button->value,
            };
        }

        $label = $this->labelText($view, $vars['label'] ?? null, $vars, true);

        return button()
            ->type($type)
            ->map(fn($btn) => $this->applyWidgetAttributes($btn, $view, $vars))
            ->mapWhen(!$this->isEmpty($vars['value'] ?? null), fn($btn) => $btn->value($this->strval($vars['value'])))
            ->child($this->arrayGetBool($vars, 'label_html')
                ? unsafeRawText($label)
                : text($label));
    }
}
