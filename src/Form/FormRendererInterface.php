<?php declare(strict_types=1);

namespace Berry\Symfony\Form;

use Berry\Html\Elements\Form;
use Berry\Element;
use Symfony\Component\Form\FormView;

interface FormRendererInterface
{
    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    public function renderForm(FormView $view, array $variables = []): Element;

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    public function formStart(FormView $view, array $variables = []): Form;

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    public function formRow(FormView $view, array $variables = []): ?Element;

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    public function formWidget(FormView $view, array $variables = []): ?Element;

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    public function formLabel(FormView $view, ?string $label = null, array $variables = []): ?Element;

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    public function formHelp(FormView $view, array $variables = []): ?Element;

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    public function formErrors(FormView $view, array $variables = []): ?Element;

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    public function formRest(FormView $view, array $variables = []): ?Element;
}
