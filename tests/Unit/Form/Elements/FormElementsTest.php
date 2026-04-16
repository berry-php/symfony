<?php declare(strict_types=1);

use Berry\Symfony\Form\Elements\ButtonFormElement;
use Berry\Symfony\Form\Elements\CheckboxFormElement;
use Berry\Symfony\Form\Elements\RadioFormElement;
use Berry\Symfony\Form\Elements\TextAreaFormElement;
use Berry\Symfony\Test\Unit\Form\Support\FormTestSupport;

test('ButtonFormElement supports button-like prefixes', function () {
    $element = new ButtonFormElement();

    expect($element->supports(FormTestSupport::field('button', ['block_prefixes' => ['form', 'button']])))->toBeTrue();
    expect($element->supports(FormTestSupport::field('submit', ['block_prefixes' => ['form', 'submit']])))->toBeTrue();
    expect($element->supports(FormTestSupport::field('reset', ['block_prefixes' => ['form', 'reset']])))->toBeTrue();
    expect($element->supports(FormTestSupport::field('text')))->toBeFalse();
});

test('ButtonFormElement chooses default types and preserves explicit value and html labels', function () {
    $element = new ButtonFormElement();
    $renderer = FormTestSupport::dummyRenderer();

    $submitHtml = $element->render(FormTestSupport::field('save_changes', [
        'block_prefixes' => ['form', 'submit'],
        'value' => 'save',
    ]), $renderer)->toString();

    $customHtml = $element->render(FormTestSupport::field('preview', [
        'block_prefixes' => ['form', 'button'],
        'type' => 'menu',
        'label' => '<strong>Preview</strong>',
        'label_html' => true,
    ]), $renderer)->toString();

    expect($submitHtml)
        ->toContain('type="submit"')
        ->toContain('value="save"')
        ->toContain('>Save changes</button>');

    expect($customHtml)
        ->toContain('type="menu"')
        ->toContain('<strong>Preview</strong>');
});

test('CheckboxFormElement renders checkbox attributes and shared aria metadata', function () {
    $element = new CheckboxFormElement();

    expect($element->supports(FormTestSupport::field('accept', ['block_prefixes' => ['form', 'checkbox']])))->toBeTrue();
    expect($element->supports(FormTestSupport::field('accept')))->toBeFalse();

    $html = $element->render(FormTestSupport::field('accept', [
        'block_prefixes' => ['form', 'checkbox'],
        'value' => 'yes',
        'checked' => true,
        'help' => 'terms.help',
        'errors' => FormTestSupport::errors('Required'),
    ]), FormTestSupport::dummyRenderer())->toString();

    expect($html)
        ->toContain('type="checkbox"')
        ->toContain('value="yes"')
        ->toContain('checked')
        ->toContain('aria-invalid')
        ->toContain('aria-describedby="accept_help accept_error"');
});

test('RadioFormElement renders radio attributes and shared aria metadata', function () {
    $element = new RadioFormElement();

    expect($element->supports(FormTestSupport::field('choice', ['block_prefixes' => ['form', 'radio']])))->toBeTrue();
    expect($element->supports(FormTestSupport::field('choice')))->toBeFalse();

    $html = $element->render(FormTestSupport::field('choice', [
        'block_prefixes' => ['form', 'radio'],
        'value' => 'a',
        'checked' => true,
        'help' => 'choice.help',
        'errors' => FormTestSupport::errors('Pick one'),
    ]), FormTestSupport::dummyRenderer())->toString();

    expect($html)
        ->toContain('type="radio"')
        ->toContain('value="a"')
        ->toContain('checked')
        ->toContain('aria-invalid')
        ->toContain('aria-describedby="choice_help choice_error"');
});

test('TextAreaFormElement renders text and shared widget attributes', function () {
    $element = new TextAreaFormElement();

    expect($element->supports(FormTestSupport::field('body', ['block_prefixes' => ['form', 'textarea']])))->toBeTrue();
    expect($element->supports(FormTestSupport::field('body')))->toBeFalse();

    $html = $element->render(FormTestSupport::field('body', [
        'block_prefixes' => ['form', 'textarea'],
        'value' => 'Long text',
        'help' => 'body.help',
        'errors' => FormTestSupport::errors('Too short'),
    ]), FormTestSupport::dummyRenderer())->toString();

    expect($html)
        ->toContain('<textarea')
        ->toContain('id="body"')
        ->toContain('name="body"')
        ->toContain('aria-invalid')
        ->toContain('aria-describedby="body_help body_error"')
        ->toContain('>Long text</textarea>');
});
