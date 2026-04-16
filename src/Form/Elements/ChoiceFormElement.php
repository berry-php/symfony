<?php declare(strict_types=1);

namespace Berry\Symfony\Form\Elements;

use Berry\Html\Elements\Option;
use Berry\Symfony\Form\FormRendererInterface;
use Berry\Element;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;

use function Berry\Html\optgroup;
use function Berry\Html\option;
use function Berry\Html\select;

final class ChoiceFormElement extends AbstractFormElement
{
    public function supports(FormView $view): bool
    {
        return $this->hasBlockPrefix($view, 'choice') && !$this->arrayGetBool($this->filterArrayStringKeys($view->vars), 'expanded');
    }

    public function render(FormView $view, FormRendererInterface $formRenderer, array $variables = []): Element
    {
        $vars = $this->vars($view, $variables);
        $multiple = $this->arrayGetBool($vars, 'multiple');
        $preferredChoices = $this->filterChoiceViews($vars['preferred_choices'] ?? []);
        $choices = $this->filterChoiceViews($vars['choices'] ?? []);

        if (!$this->arrayGetBool($vars, 'duplicate_preferred_choices', true) && count($preferredChoices) > 0) {
            $choices = $this->removeChoiceValues($choices, $this->choiceValues($preferredChoices));
        }

        $placeholder = $vars['placeholder'] ?? null;

        return select()
            ->map(fn($select) => $this->applyWidgetAttributes($select, $view, $vars))
            ->multiple($multiple)
            ->children([
                ...(!$multiple && $placeholder !== null ? [
                    option()
                        ->value('')
                        ->selected($this->isSelected($vars, ''))
                        ->map(fn(Option $option): Option => $this->applyElementAttributes($option, $vars['placeholder_attr'] ?? []))
                        ->text($this->trans($placeholder, [], $this->transDomain($vars, 'choice_translation_domain'))),
                ] : []),
                ...$this->renderChoices($preferredChoices, $vars),
                ...(count($preferredChoices) > 0 && count($choices) > 0 ? [
                    option()
                        ->disabled()
                        ->text($this->arrayGetString($vars, 'separator', '-------------------')),
                ] : []),
                ...$this->renderChoices($choices, $vars),
            ]);
    }

    /**
     * @param mixed $choices
     * @return array<int|string, ChoiceGroupView|ChoiceView>
     */
    private function filterChoiceViews(mixed $choices): array
    {
        if (!is_array($choices)) {
            return [];
        }

        return array_filter(
            $choices,
            static fn(mixed $choice): bool => $choice instanceof ChoiceView || $choice instanceof ChoiceGroupView,
        );
    }

    /**
     * @param ChoiceGroupView|ChoiceView $choice
     * @param array<string, mixed> $vars
     */
    private function renderChoice(ChoiceGroupView|ChoiceView $choice, array $vars): Element
    {
        if ($choice instanceof ChoiceGroupView) {
            return optgroup()
                ->label($this->trans($choice->label, [], $this->transDomain($vars, 'choice_translation_domain')))
                ->children($this->renderChoices($this->filterChoiceViews($choice->choices), $vars));
        }

        return option()
            ->value($choice->value)
            ->selected($this->isSelected($vars, $choice->value))
            ->map(fn(Option $option): Option => $this->applyElementAttributes($option, $choice->attr))
            ->text($this->trans(
                $choice->label,
                $this->filterArrayStringKeys($choice->labelTranslationParameters),
                $this->transDomain($vars, 'choice_translation_domain')
            ));
    }

    /**
     * @param array<int|string, ChoiceGroupView|ChoiceView> $choices
     * @param array<string, mixed> $vars
     * @return Element[]
     */
    private function renderChoices(array $choices, array $vars): array
    {
        return array_map(
            fn(ChoiceGroupView|ChoiceView $choice): Element => $this->renderChoice($choice, $vars),
            array_values($choices),
        );
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function isSelected(array $vars, string $choiceValue): bool
    {
        $isSelected = $vars['is_selected'] ?? null;

        if (is_callable($isSelected)) {
            $value = $vars['value'] ?? ($this->arrayGetBool($vars, 'multiple') ? [] : '');

            if ($this->arrayGetBool($vars, 'multiple') && !is_array($value)) {
                $value = [];
            }

            return (bool) $isSelected($choiceValue, $value);
        }

        $value = $vars['value'] ?? null;

        if (is_array($value)) {
            return in_array($choiceValue, array_map($this->strval(...), $value), true);
        }

        return $choiceValue === $this->strval($value);
    }

    /**
     * @param array<int|string, ChoiceGroupView|ChoiceView> $choices
     * @return array<string, true>
     */
    private function choiceValues(array $choices): array
    {
        $values = [];

        foreach ($choices as $choice) {
            if ($choice instanceof ChoiceView) {
                $values[$choice->value] = true;
                continue;
            }

            foreach ($this->choiceValues($this->filterChoiceViews($choice->choices)) as $value => $_) {
                $values[$value] = true;
            }
        }

        return $values;
    }

    /**
     * @param array<int|string, ChoiceGroupView|ChoiceView> $choices
     * @param array<string, true> $values
     * @return array<int|string, ChoiceGroupView|ChoiceView>
     */
    private function removeChoiceValues(array $choices, array $values): array
    {
        $filtered = [];

        foreach ($choices as $key => $choice) {
            if ($choice instanceof ChoiceView) {
                if (!isset($values[$choice->value])) {
                    $filtered[$key] = $choice;
                }

                continue;
            }

            $remainingChoices = $this->removeChoiceValues($this->filterChoiceViews($choice->choices), $values);
            if (count($remainingChoices) === 0) {
                continue;
            }

            $group = new ChoiceGroupView($choice->label, $remainingChoices);
            $filtered[$key] = $group;
        }

        return $filtered;
    }
}
