<?php declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Html\Elements\Form;
use Berry\Symfony\Form\FormRendererInterface;
use Berry\Symfony\Locator\ComponentServiceLocator;
use Berry\Element;
use Symfony\Component\Form\FormView;

trait WithFormRendering
{
    protected ?FormRendererInterface $formRendererLocator = null;

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    protected function renderForm(FormView $view, array $variables = []): Element
    {
        return ($this->formRendererLocator ?? ComponentServiceLocator::getFormRenderer())->renderForm($view, $variables);
    }

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    protected function formStart(FormView $view, array $variables = []): Form
    {
        return ($this->formRendererLocator ?? ComponentServiceLocator::getFormRenderer())->formStart($view, $variables);
    }

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    protected function formRow(FormView $view, array $variables = []): ?Element
    {
        return ($this->formRendererLocator ?? ComponentServiceLocator::getFormRenderer())->formRow($view, $variables);
    }

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    protected function formWidget(FormView $view, array $variables = []): ?Element
    {
        return ($this->formRendererLocator ?? ComponentServiceLocator::getFormRenderer())->formWidget($view, $variables);
    }

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    protected function formLabel(FormView $view, ?string $label = null, array $variables = []): ?Element
    {
        return ($this->formRendererLocator ?? ComponentServiceLocator::getFormRenderer())->formLabel($view, $label, $variables);
    }

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    protected function formHelp(FormView $view, array $variables = []): ?Element
    {
        return ($this->formRendererLocator ?? ComponentServiceLocator::getFormRenderer())->formHelp($view, $variables);
    }

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    protected function formErrors(FormView $view, array $variables = []): ?Element
    {
        return ($this->formRendererLocator ?? ComponentServiceLocator::getFormRenderer())->formErrors($view, $variables);
    }

    /**
     * @param FormView $view
     * @param array<string, mixed> $variables
     */
    protected function formRest(FormView $view, array $variables = []): ?Element
    {
        return ($this->formRendererLocator ?? ComponentServiceLocator::getFormRenderer())->formRest($view, $variables);
    }
}
