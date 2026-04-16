<?php declare(strict_types=1);

namespace Berry\Symfony\Form\Elements;

use Berry\Html\Enums\InputType;
use Berry\Symfony\Form\FormRendererInterface;
use Berry\Element;
use Symfony\Component\Form\FormView;

use function Berry\Html\input;

final class CheckboxFormElement extends AbstractFormElement
{
    public function supports(FormView $view): bool
    {
        return $this->hasBlockPrefix($view, 'checkbox') && !$this->hasBlockPrefix($view, 'radio');
    }

    public function render(FormView $view, FormRendererInterface $formRenderer, array $variables = []): Element
    {
        $vars = $this->vars($view, $variables);

        return input()
            ->type(InputType::Checkbox)
            ->map(fn($input) => $this->applyWidgetAttributes($input, $view, $vars))
            ->mapWhen(array_key_exists('value', $vars), fn($input) => $input->value($this->arrayGetString($vars, 'value')))
            ->checked($this->arrayGetBool($vars, 'checked'));
    }
}
