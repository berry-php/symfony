<?php declare(strict_types=1);

namespace Berry\Symfony\Form;

use Symfony\Component\Form\FormView;

final class FormElementRegistry
{
    public const FORM_ELEMENT_TAG = 'berry.form_element';

    /**
     * @var FormElementInterface[]
     */
    private array $elements = [];

    /**
     * @param iterable<FormElementInterface> $elements
     */
    public function __construct(iterable $elements = [])
    {
        $this->elements = iterator_to_array($elements);

        usort(
            $this->elements,
            static fn($a, $b): int => $b->priority() <=> $a->priority(),
        );
    }

    public function resolve(FormView $view): ?FormElementInterface
    {
        foreach ($this->elements as $element) {
            if ($element->supports($view)) {
                return $element;
            }
        }

        return null;
    }
}
