<?php declare(strict_types=1);

namespace Berry\Symfony\Test\Unit\Form\Support;

use ArrayIterator;
use Berry\Element;
use Berry\Symfony\Form\Elements\ButtonFormElement;
use Berry\Symfony\Form\Elements\CheckboxFormElement;
use Berry\Symfony\Form\Elements\ChoiceFormElement;
use Berry\Symfony\Form\Elements\RadioFormElement;
use Berry\Symfony\Form\Elements\TextAreaFormElement;
use Berry\Symfony\Form\FormElementInterface;
use Berry\Symfony\Form\FormElementRegistry;
use Berry\Symfony\Form\FormRenderer;
use Berry\Symfony\Form\FormRendererInterface;
use LogicException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormTestSupport
{
    /**
     * @return ArrayIterator<int, FormError>
     */
    public static function errors(string ...$messages): ArrayIterator
    {
        return new ArrayIterator(array_values(array_map(
            static fn(string $message): FormError => new FormError($message),
            $messages,
        )));
    }

    /**
     * @param array<string, mixed> $vars
     * @param array<int|string, FormView> $children
     */
    public static function view(array $vars = [], array $children = []): FormView
    {
        $name = is_string($vars['name'] ?? null) ? $vars['name'] : 'field';
        $id = is_string($vars['id'] ?? null) ? $vars['id'] : $name;
        $fullName = is_string($vars['full_name'] ?? null) ? $vars['full_name'] : $name;
        $blockPrefixes = is_array($vars['block_prefixes'] ?? null)
            ? $vars['block_prefixes']
            : ['form', 'text'];

        $view = new FormView();
        $view->vars = array_replace([
            'id' => $id,
            'name' => $name,
            'full_name' => $fullName,
            'method' => 'POST',
            'action' => '',
            'multipart' => false,
            'compound' => false,
            'required' => false,
            'disabled' => false,
            'label' => null,
            'label_html' => false,
            'label_attr' => [],
            'label_format' => null,
            'help' => null,
            'help_html' => false,
            'help_attr' => [],
            'errors' => self::errors(),
            'attr' => [],
            'row_attr' => [],
            'translation_domain' => null,
            'attr_translation_parameters' => [],
            'label_translation_parameters' => [],
            'help_translation_parameters' => [],
            'block_prefixes' => $blockPrefixes,
            'value' => null,
            'checked' => false,
        ], $vars);

        foreach ($children as $childName => $child) {
            $child->parent = $view;
            $view->children[$childName] = $child;
        }

        return $view;
    }

    /**
     * @param array<string, mixed> $vars
     * @param array<int|string, FormView> $children
     */
    public static function field(string $name, array $vars = [], array $children = []): FormView
    {
        return self::view(array_replace([
            'id' => $name,
            'name' => $name,
            'full_name' => $name,
        ], $vars), $children);
    }

    /**
     * @param iterable<FormElementInterface>|null $elements
     */
    public static function renderer(?iterable $elements = null, ?TranslatorInterface $translator = null): FormRenderer
    {
        $elements ??= [
            new ButtonFormElement($translator),
            new CheckboxFormElement($translator),
            new ChoiceFormElement($translator),
            new RadioFormElement($translator),
            new TextAreaFormElement($translator),
        ];

        return new FormRenderer(new FormElementRegistry($elements), $translator);
    }

    public static function dummyRenderer(): FormRendererInterface
    {
        return new class implements FormRendererInterface {
            public function renderForm(FormView $view, array $variables = []): Element
            {
                throw new LogicException('Unused in this test');
            }

            public function formStart(FormView $view, array $variables = []): \Berry\Html\Elements\Form
            {
                throw new LogicException('Unused in this test');
            }

            public function formRow(FormView $view, array $variables = []): ?Element
            {
                throw new LogicException('Unused in this test');
            }

            public function formWidget(FormView $view, array $variables = []): ?Element
            {
                throw new LogicException('Unused in this test');
            }

            public function formLabel(FormView $view, ?string $label = null, array $variables = []): ?Element
            {
                throw new LogicException('Unused in this test');
            }

            public function formHelp(FormView $view, array $variables = []): ?Element
            {
                throw new LogicException('Unused in this test');
            }

            public function formErrors(FormView $view, array $variables = []): ?Element
            {
                throw new LogicException('Unused in this test');
            }

            public function formRest(FormView $view, array $variables = []): ?Element
            {
                throw new LogicException('Unused in this test');
            }
        };
    }
}
