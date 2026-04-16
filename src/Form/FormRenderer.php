<?php declare(strict_types=1);

namespace Berry\Symfony\Form;

use Berry\Html\Elements\Div;
use Berry\Html\Elements\Form;
use Berry\Html\Enums\InputType;
use Berry\Element;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Berry\Html\div;
use function Berry\Html\fieldset;
use function Berry\Html\form;
use function Berry\Html\input;
use function Berry\Html\label;
use function Berry\Html\legend;
use function Berry\Html\li;
use function Berry\Html\ul;
use function Berry\fragment;
use function Berry\text;
use function Berry\unsafeRawText;

/**
 * Renders Symfony Forms with Berry
 */
final class FormRenderer implements FormRendererInterface
{
    use WithFormRenderUtils;

    public function __construct(
        private FormElementRegistry $formElementRegistry,
        ?TranslatorInterface $translator = null,
    ) {
        $this->translator = $translator;
    }

    public function renderForm(FormView $view, array $variables = []): Element
    {
        return $this
            ->formStart($view, $variables)
            ->child($this->formWidget($view, $variables));
    }

    public function formStart(FormView $view, array $variables = []): Form
    {
        $vars = $this->vars($view, $variables);

        $method = strtoupper($this->arrayGetString($vars, 'method', 'POST'));
        $browserMethod = in_array($method, ['GET', 'POST'], true) ? $method : 'POST';

        $name = $this->arrayGetString($vars, 'name');
        $action = $this->arrayGetString($vars, 'action');
        $isMultipart = $this->arrayGetBool($vars, 'multipart');

        $formElem = form()
            ->method($browserMethod)
            ->mapWhen($name !== '', fn($form) => $form->name($name))
            ->mapWhen($action !== '', fn($form) => $form->action($action))
            ->map(fn($form) => $this->applyElementAttributes($form, $vars['attr'] ?? []))
            ->mapWhen($isMultipart, fn($form) => $form->enctype('multipart/form-data'))
            ->childWhen($browserMethod !== $method, fn() => input()
                ->type(InputType::Hidden)
                ->name('_method')
                ->value($method));

        $view->setMethodRendered();
        return $formElem;
    }

    public function formRow(FormView $view, array $variables = []): ?Element
    {
        $vars = $this->vars($view, $variables);

        if ($this->hasBlockPrefix($view, ['hidden'])) {
            return $this->formWidget($view, $variables);
        }

        if ($this->hasBlockPrefix($view, ['button', 'submit', 'reset'])) {
            return div()
                ->map(fn($elem) => $this->applyElementAttributes($elem, $vars['row_attr'] ?? []))
                ->child($this->formWidget($view, $variables));
        }

        if ($this->arrayGetBool($vars, 'compound')) {
            return fieldset()
                ->map(fn($elem) => $this->applyElementAttributes($elem, $vars['row_attr'] ?? []))
                ->child($this->formLabel($view, null, $variables))
                ->child($this->formErrors($view, $variables))
                ->child($this->formWidget($view, $variables))
                ->child($this->formHelp($view, $variables));
        }

        return div()
            ->map(fn($elem) => $this->applyElementAttributes($elem, $vars['row_attr'] ?? []))
            ->child($this->formLabel($view, null, $variables))
            ->child($this->formErrors($view, $variables))
            ->child($this->formWidget($view, $variables))
            ->child($this->formHelp($view, $variables));
    }

    public function formWidget(FormView $view, array $variables = []): ?Element
    {
        $element = $this->formElementRegistry->resolve($view);
        if ($element !== null) {
            $view->setRendered();
            return $element->render($view, $this, $variables);
        }

        $vars = $this->vars($view, $variables);

        if ($this->arrayGetBool($vars, 'compound')) {
            $containerVars = $vars;
            $containerVars['attr'] = $this->normalizeAttributes($vars['attr'] ?? []);

            $prototype = $vars['prototype'] ?? null;

            if ($prototype instanceof FormView && !$prototype->isRendered()) {
                $containerVars['attr']['data-prototype'] = $this->formRow($prototype)?->toString();
            }

            $view->setRendered();

            return div()
                ->map(fn(Div $div): Div => $this->applyWidgetContainerAttributes($div, $view, $containerVars))
                ->childWhen($view->parent === null, fn() => $this->formErrors($view, $variables))
                ->children($this->unrenderedChildren($view))
                ->child($this->formRest($view, $variables));
        }

        $type = $this->hasBlockPrefix($view, ['hidden'])
            ? InputType::Hidden
            : InputType::tryFrom($this->strval($vars['type'] ?? 'text')) ?? InputType::Text;

        $input = input()
            ->type($type)
            ->map(fn($input) => $this->applyWidgetAttributes($input, $view, $vars))
            ->mapWhen(!$this->isEmpty($vars['value'] ?? null), fn($input) => $input->value($this->strval($vars['value'])));

        $view->setRendered();

        return $input;
    }

    public function formLabel(FormView $view, ?string $label = null, array $variables = []): ?Element
    {
        $vars = $this->vars($view, $variables);

        $resolvedLabel = $label ?? ($vars['label'] ?? null);

        if ($resolvedLabel === false) {
            return null;
        }

        $isCompound = $this->arrayGetBool($vars, 'compound');
        $isHtmlText = $this->arrayGetBool($vars, 'label_html');

        $labelText = $this->labelText($view, $resolvedLabel, $variables);

        if ($isCompound) {
            return legend()
                ->mapWhen($this->arrayGetBool($vars, 'required'), fn($label) => $label->class('required'))
                ->map(fn($label) => $this->applyElementAttributes($label, $vars['label_attr'] ?? []))
                ->child($isHtmlText
                    ? unsafeRawText($labelText)
                    : text($labelText));
        }

        return label()
            ->mapWhen(($this->arrayGetString($vars, 'id')) !== '', fn($label) => $label->for($this->viewId($vars)))
            ->mapWhen($this->arrayGetBool($vars, 'required'), fn($label) => $label->class('required'))
            ->map(fn($label) => $this->applyElementAttributes($label, $vars['label_attr'] ?? []))
            ->child($isHtmlText
                ? unsafeRawText($labelText)
                : text($labelText));
    }

    public function formHelp(FormView $view, array $variables = []): ?Element
    {
        $vars = $this->vars($view, $variables);

        $help = $vars['help'] ?? null;

        if ($help === null || $help === false) {
            return null;
        }

        $helpAttr = $vars['help_attr'] ?? [];
        if (!is_array($helpAttr)) {
            $helpAttr = [];
        }

        $helpAttr['id'] = $vars['help_id'] ?? $this->helpId($this->viewId($vars));

        $isHtmlText = $this->arrayGetBool($vars, 'help_html');
        $helpText = $this->trans($help, $this->transParams($vars, 'help_translation_parameters'), $this->transDomain($vars));

        return div()
            ->class('help-text')
            ->map(fn($el) => $this->applyElementAttributes($el, $helpAttr))
            ->child($isHtmlText ? unsafeRawText($helpText) : text($helpText));
    }

    public function formErrors(FormView $view, array $variables = []): ?Element
    {
        $errors = $this->errors($view);
        if (count($errors) === 0) {
            return null;
        }

        $vars = $this->vars($view, $variables);
        $id = $this->viewId($vars);

        return ul()
            ->id($this->errorId($id))
            ->children(array_map(
                fn(FormError $err): Element => li()->text($err->getMessage()),
                $errors,
                array_keys($errors),
            ));
    }

    public function formRest(FormView $view, array $variables = []): Element
    {
        $vars = $this->vars($view, $variables);
        $children = $this->unrenderedChildren($view);

        if ($view->parent === null && !$view->isMethodRendered()) {
            $method = strtoupper($this->arrayGetString($vars, 'method', 'POST'));
            $browserMethod = in_array($method, ['GET', 'POST'], true) ? $method : 'POST';

            if ($browserMethod !== $method) {
                $children[] = input()
                    ->type(InputType::Hidden)
                    ->name('_method')
                    ->value($method);
            }
        }

        return fragment(...array_values($children));
    }

    /**
     * @return Element[]
     */
    protected function unrenderedChildren(FormView $view): array
    {
        $children = [];

        foreach ($view->children as $child) {
            if ($child->isRendered()) {
                continue;
            }

            $row = $this->formRow($child);
            if ($row === null) {
                continue;
            }

            $children[] = $row;
        }

        return $children;
    }
}
