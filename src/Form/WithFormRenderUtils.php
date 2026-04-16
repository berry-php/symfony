<?php declare(strict_types=1);

namespace Berry\Symfony\Form;

use Berry\Html\HtmlTag;
use Berry\Html\HtmlVoidTag;
use Berry\Element;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Closure;
use LogicException;
use Stringable;

trait WithFormRenderUtils
{
    protected ?TranslatorInterface $translator = null;

    protected function strval(mixed $value): string
    {
        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $type = get_debug_type($value);
        throw new LogicException("Expected a scalar stringable value, got '{$type}'");
    }

    /**
     * @param array<string, mixed> $vars
     */
    protected function arrayGetString(array $vars, string $key, string $default = ''): string
    {
        return $this->strval($vars[$key] ?? $default);
    }

    /**
     * @param array<string, mixed> $vars
     */
    protected function arrayGetBool(array $vars, string $key, bool $default = false): bool
    {
        return (bool) ($vars[$key] ?? $default);
    }

    protected function humanize(string $text): string
    {
        return ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $text))));
    }

    /**
     * @template T of HtmlTag|HtmlVoidTag
     * @param T $tag
     * @return T
     */
    protected function applyElementAttributes(HtmlTag|HtmlVoidTag $tag, mixed $attributes): HtmlTag|HtmlVoidTag
    {
        foreach (self::normalizeAttributes($attributes) as $name => $value) {
            if ($value === false || $value === null || $name === '') {
                continue;
            }

            if ($value === true) {
                $tag->flag($name, escapeKey: false);
                continue;
            }

            if ($name === 'class' && $tag instanceof HtmlTag) {
                $tag->class((string) $value);
                continue;
            }

            $tag->attr($name, (string) $value, escapeKey: false);
        }

        return $tag;
    }

    /**
     * @return array<string, scalar|bool|null>
     */
    protected function normalizeAttributes(mixed $attributes): array
    {
        if (!is_array($attributes)) {
            return [];
        }

        $normalized = [];

        foreach ($attributes as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            if ($value instanceof Stringable) {
                $normalized[$name] = (string) $value;
                continue;
            }

            if ($value === null || is_scalar($value)) {
                $normalized[$name] = $value;
            }
        }

        return $normalized;
    }

    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && count($value) === 0);
    }

    /**
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
     */
    protected function vars(FormView $view, array $variables): array
    {
        $vars = $this->filterArrayStringKeys($view->vars);

        foreach (['attr', 'row_attr', 'label_attr', 'help_attr'] as $attributeKey) {
            if (array_key_exists($attributeKey, $variables) && is_array($variables[$attributeKey] ?? null)) {
                $base = is_array($vars[$attributeKey] ?? null) ? $vars[$attributeKey] : [];
                $vars[$attributeKey] = array_replace($base, $variables[$attributeKey]);
                unset($variables[$attributeKey]);
            }
        }

        return array_replace($vars, $variables);
    }

    /**
     * @param string|string[] $prefixes
     */
    protected function hasBlockPrefix(FormView $view, string|array $prefixes): bool
    {
        if (!is_array($prefixes)) {
            $prefixes = [$prefixes];
        }

        $blockPrefixes = $view->vars['block_prefixes'] ?? null;

        if (!is_array($blockPrefixes)) {
            return false;
        }

        foreach ($blockPrefixes as $prefix) {
            if (!is_string($prefix)) {
                continue;
            }

            if (in_array($prefix, $prefixes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @template Tag of HtmlTag|HtmlVoidTag
     * @param Tag $tag
     * @param array<string, mixed> $variables
     * @return Tag
     */
    protected function applyWidgetAttributes(HtmlTag|HtmlVoidTag $tag, FormView $view, array $variables): HtmlTag|HtmlVoidTag
    {
        $id = $this->viewId($variables);

        $tag->attr('id', $id, escapeKey: false);
        $tag->attr('name', $this->strval($variables['full_name'] ?? ''), escapeKey: false);

        if ($this->arrayGetBool($variables, 'required')) {
            $tag->flag('required');
        }

        if ($this->arrayGetBool($variables, 'disabled')) {
            $tag->flag('disabled');
        }

        /** @var string[] $describedBy */
        $describedBy = [];

        $help = $variables['help'] ?? null;

        if ($help !== null && $help !== false) {
            $describedBy[] = $this->arrayGetString($variables, 'help_id', $this->helpId($id));
        }

        if (count($this->errors($view)) > 0) {
            $tag->ariaInvalid();
            $describedBy[] = $this->errorId($id);
        }

        if (count($describedBy) > 0) {
            $tag->ariaDescribedby(implode(' ', $describedBy));
        }

        return $this->applyElementAttributes($tag, $this->translatedAttributes($variables['attr'] ?? [], $variables));
    }

    /**
     * @template Tag of HtmlTag|HtmlVoidTag
     * @param Tag $tag
     * @param array<string, mixed> $variables
     * @return Tag
     */
    protected function applyWidgetContainerAttributes(HtmlTag|HtmlVoidTag $tag, FormView $view, array $variables): HtmlTag|HtmlVoidTag
    {
        $id = $this->viewId($variables);

        if ($id !== '') {
            $tag->attr('id', $this->viewId($variables), escapeKey: false);
        }

        /** @var string[] $describedBy */
        $describedBy = [];

        $help = $variables['help'] ?? null;

        if ($help !== null && $help !== false) {
            $describedBy[] = $this->arrayGetString($variables, 'help_id', $this->helpId($id));
        }

        if (count($this->errors($view)) > 0) {
            $tag->ariaInvalid();
            $describedBy[] = $this->errorId($id);
        }

        if (count($describedBy) > 0) {
            $tag->ariaDescribedby(implode(' ', $describedBy));
        }

        return $this->applyElementAttributes($tag, $this->translatedAttributes($variables['attr'] ?? [], $variables));
    }

    /**
     * @param mixed $attributes
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
     */
    protected function translatedAttributes(mixed $attributes, array $variables): array
    {
        $attributes = $this->normalizeAttributes($attributes);

        $domain = $variables['translation_domain'] ?? null;
        assert(is_string($domain) || $domain === null || $domain === false);

        $attrTransParams = $variables['attr_translation_parameters'] ?? [];
        if (!is_array($attrTransParams)) {
            $attrTransParams = [];
        }

        $transParams = $this->filterArrayStringKeys($attrTransParams);

        foreach (['placeholder', 'title'] as $name) {
            if (!array_key_exists($name, $attributes) || $attributes[$name] === null || $attributes[$name] === false) {
                continue;
            }

            $attributes[$name] = $domain === false
                ? $this->strval($attributes[$name])
                : $this->trans($attributes[$name], $transParams, $domain);
        }

        return $attributes;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function trans(mixed $text, array $parameters = [], string|null|false $domain = null): string
    {
        if ($text instanceof TranslatableInterface && $this->translator !== null) {
            return $text->trans($this->translator);
        }

        $text = $this->strval($text);

        if ($domain === false || $this->translator === null) {
            return $text;
        }

        return $this->translator->trans($text, $parameters, $domain);
    }

    /**
     * @param array<string, mixed> $variables
     */
    protected function labelText(FormView $view, mixed $label, array $variables = [], bool $button = false): string
    {
        $vars = $this->vars($view, $variables);

        if ($label === null || $label === '') {
            $labelFormat = $vars['label_format'] ?? null;

            $label = is_string($labelFormat) && $labelFormat !== ''
                ? str_replace(
                    [
                        '%name%',
                        '%id%',
                    ],
                    [
                        $this->strval($vars['name'] ?? ''),
                        $this->strval($vars['id'] ?? ''),
                    ],
                    $labelFormat,
                )
                : $this->humanize($this->strval($vars['name'] ?? ''));
        }

        $domain = $this->transDomain($vars);
        $transParams = $this->transParams($vars, 'label_translation_parameters');
        $translated = $this->trans($label, $transParams, $domain);

        return $translated === '' && $button
            ? $this->humanize($this->strval($vars['name'] ?? ''))
            : $translated;
    }

    /**
     * @param array<string, mixed> $vars
     */
    protected function viewId(array $vars): string
    {
        return $this->arrayGetString($vars, 'id');
    }

    protected function helpId(string $id): string
    {
        return sprintf('%s_help', $id);
    }

    protected function errorId(string $id): string
    {
        return sprintf('%s_error', $id);
    }

    /**
     * @param array<mixed, mixed> $vars
     * @return array<string, mixed>
     */
    protected function filterArrayStringKeys(array $vars): array
    {
        return array_filter($vars, fn(mixed $key) => is_string($key), ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
     */
    protected function transParams(array $variables, string $key): array
    {
        $transParams = $variables[$key] ?? [];
        if (!is_array($transParams)) {
            return [];
        }

        return $this->filterArrayStringKeys($transParams);
    }

    /**
     * @param array<string, mixed> $variables
     */
    protected function transDomain(array $variables, string $key = 'translation_domain'): string|null|false
    {
        $domain = $variables[$key] ?? null;
        assert(is_string($domain) || $domain === null || $domain === false);
        return $domain;
    }

    /**
     * @return FormError[]
     */
    protected function errors(FormView $view): array
    {
        $errors = $view->vars['errors'] ?? null;

        if (!is_iterable($errors)) {
            return [];
        }

        return array_filter(iterator_to_array($errors), fn($err) => $err instanceof FormError);
    }
}
