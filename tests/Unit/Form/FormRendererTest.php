<?php declare(strict_types=1);

use Berry\Symfony\Form\FormElementInterface;
use Berry\Symfony\Form\FormRendererInterface;
use Berry\Symfony\Test\Unit\Form\Support\FormTestSupport;
use Berry\Element;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Berry\Html\div;

test('renderForm wraps the widget in a form tag', function () {
    $renderer = FormTestSupport::renderer([]);

    $html = $renderer->renderForm(FormTestSupport::field('email'))->toString();

    expect($html)
        ->toContain('<form method="POST" name="email">')
        ->toContain('<input type="text" id="email" name="email" />')
        ->toContain('</form>');
});

test('formStart renders browser and spoofed methods with merged attributes', function () {
    $renderer = FormTestSupport::renderer();

    $get = FormTestSupport::field('search', [
        'method' => 'GET',
        'action' => '/search',
        'attr' => ['class' => 'base'],
    ]);

    $getHtml = $renderer->formStart($get, ['attr' => ['data-track' => '1']])->toString();

    expect($getHtml)
        ->toContain('<form')
        ->toContain('method="GET"')
        ->toContain('action="/search"')
        ->toContain('class="base"')
        ->toContain('data-track="1"');

    expect(str_contains($getHtml, 'name="_method"'))->toBeFalse();

    expect($get->isMethodRendered())->toBeTrue();

    $put = FormTestSupport::field('profile', [
        'method' => 'PUT',
        'action' => '/profiles/1',
        'multipart' => true,
        'attr' => ['class' => 'stack'],
    ]);

    $putHtml = $renderer->formStart($put, ['attr' => ['data-hotwire' => 'false']])->toString();

    expect($putHtml)
        ->toContain('method="POST"')
        ->toContain('name="profile"')
        ->toContain('action="/profiles/1"')
        ->toContain('enctype="multipart/form-data"')
        ->toContain('class="stack"')
        ->toContain('data-hotwire="false"')
        ->toContain('<input type="hidden" name="_method" value="PUT" />');
});

test('formRow renders hidden fields directly and button rows without chrome', function () {
    $renderer = FormTestSupport::renderer();

    $hiddenHtml = $renderer->formRow(FormTestSupport::field('token', [
        'block_prefixes' => ['form', 'hidden'],
        'value' => 'csrf',
    ]))?->toString() ?? '';

    expect($hiddenHtml)
        ->toContain('<input type="hidden"');

    expect(str_contains($hiddenHtml, '<div'))->toBeFalse();

    $buttonHtml = $renderer->formRow(FormTestSupport::field('save', [
        'block_prefixes' => ['form', 'submit'],
        'label' => 'Save',
        'help' => 'ignored',
        'errors' => FormTestSupport::errors('ignored'),
        'row_attr' => ['class' => 'actions'],
    ]))?->toString() ?? '';

    expect($buttonHtml)
        ->toContain('<div class="actions">')
        ->toContain('<button')
        ->toContain('>Save</button>');

    expect(str_contains($buttonHtml, '<label'))->toBeFalse();
    expect(str_contains($buttonHtml, '<ul'))->toBeFalse();
    expect(str_contains($buttonHtml, 'help-text'))->toBeFalse();
});

test('formWidget renders simple inputs with translated attrs and aria metadata', function () {
    $translator = new class implements TranslatorInterface {
        /**
         * @param array<string, mixed> $parameters
         */
        public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
        {
            return 'tr:' . $id;
        }

        public function getLocale(): string
        {
            return 'en';
        }
    };

    $renderer = FormTestSupport::renderer(translator: $translator);
    $view = FormTestSupport::field('email', [
        'id' => 'contact_email',
        'full_name' => 'contact[email]',
        'value' => 'chris@example.com',
        'required' => true,
        'disabled' => true,
        'help' => 'help.key',
        'errors' => FormTestSupport::errors('Invalid email'),
        'attr' => [
            'placeholder' => 'placeholder.key',
            'title' => 'title.key',
            'class' => 'input-lg',
        ],
    ]);

    $html = $renderer->formWidget($view)?->toString() ?? '';

    expect($html)
        ->toContain('type="text"')
        ->toContain('id="contact_email"')
        ->toContain('name="contact[email]"')
        ->toContain('required')
        ->toContain('disabled')
        ->toContain('value="chris@example.com"')
        ->toContain('aria-invalid')
        ->toContain('aria-describedby="contact_email_help contact_email_error"')
        ->toContain('placeholder="tr:placeholder.key"')
        ->toContain('title="tr:title.key"')
        ->toContain('class="input-lg"');

    expect($view->isRendered())->toBeTrue();
});

test('formWidget falls back to text for unknown input types', function () {
    $renderer = FormTestSupport::renderer([]);

    $html = $renderer->formWidget(FormTestSupport::field('status', ['type' => 'strange']))?->toString() ?? '';

    expect($html)->toContain('type="text"');
});

test('formWidget renders collapsed choice fields as a select element', function () {
    $translator = new class implements TranslatorInterface {
        /**
         * @param array<string, mixed> $parameters
         */
        public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
        {
            return 'tr:' . $id;
        }

        public function getLocale(): string
        {
            return 'en';
        }
    };

    $factory = Forms::createFormFactory();
    $form = $factory
        ->createBuilder()
        ->add('status', ChoiceType::class, [
            'placeholder' => 'Pick one',
            'choices' => ['Draft' => 'draft', 'Live' => 'live'],
            'choice_translation_domain' => 'messages',
            'data' => 'live',
        ])
        ->getForm();

    $renderer = FormTestSupport::renderer(translator: $translator);
    $html = $renderer->formWidget($form->createView()['status'])?->toString() ?? '';

    expect($html)
        ->toContain('<select')
        ->toContain('id="form_status"')
        ->toContain('name="form[status]"')
        ->toContain('tr:Pick one')
        ->toContain('<option value="draft">tr:Draft</option>')
        ->toContain('<option value="live" selected>tr:Live</option>');

    expect(str_contains($html, 'type="text"'))->toBeFalse();
});

test('formWidget renders compound roots with root errors children and rest', function () {
    $renderer = FormTestSupport::renderer();
    $title = FormTestSupport::field('title', [
        'id' => 'post_title',
        'full_name' => 'post[title]',
        'label' => 'Title',
    ]);

    $root = FormTestSupport::view([
        'id' => 'post',
        'name' => 'post',
        'full_name' => 'post',
        'compound' => true,
        'method' => 'DELETE',
        'errors' => FormTestSupport::errors('Broken form'),
    ], ['title' => $title]);

    $html = $renderer->formWidget($root)?->toString() ?? '';

    expect($html)
        ->toContain('<div id="post"')
        ->toContain('<ul id="post_error"><li>Broken form</li></ul>')
        ->toContain('<label for="post_title">Title</label>')
        ->toContain('name="post[title]"')
        ->toContain('<input type="hidden" name="_method" value="DELETE" />');

    expect($root->isRendered())->toBeTrue();
    expect($title->isRendered())->toBeTrue();
});

test('formWidget keeps compound container attrs and renders collection prototypes', function () {
    $translator = new class implements TranslatorInterface {
        /**
         * @param array<string, mixed> $parameters
         */
        public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
        {
            return 'tr:' . $id;
        }

        public function getLocale(): string
        {
            return 'en';
        }
    };

    $renderer = FormTestSupport::renderer(translator: $translator);
    $prototype = FormTestSupport::field('__name__', [
        'id' => 'items___name__',
        'full_name' => 'items[__name__]',
        'label' => 'Item',
    ]);

    $html = $renderer->formWidget(FormTestSupport::view([
        'id' => 'items',
        'name' => 'items',
        'full_name' => 'items',
        'compound' => true,
        'attr' => ['class' => 'grid', 'title' => 'items.title'],
        'prototype' => $prototype,
    ]))?->toString() ?? '';

    expect($html)
        ->toContain('<div id="items"')
        ->toContain('class="grid"')
        ->toContain('title="tr:items.title"')
        ->toContain('data-prototype="&lt;div&gt;&lt;label for=&quot;items___name__&quot;&gt;tr:Item&lt;/label&gt;');

    expect($prototype->isRendered())->toBeTrue();
});

test('formRow renders expanded single choice fields with fieldset semantics and radios', function () {
    $factory = Forms::createFormFactory();
    $form = $factory
        ->createBuilder()
        ->add('status', ChoiceType::class, [
            'expanded' => true,
            'multiple' => false,
            'choices' => ['Draft' => 'draft', 'Live' => 'live'],
        ])
        ->getForm();

    $renderer = FormTestSupport::renderer();
    $html = $renderer->formRow($form->createView()['status'])?->toString() ?? '';

    expect($html)
        ->toContain('<fieldset>')
        ->toContain('<legend class="required">Status</legend>')
        ->toContain('type="radio"');

    expect(str_contains($html, 'type="checkbox"'))->toBeFalse();
});

test('formLabel handles false label formats required classes and html labels', function () {
    $renderer = FormTestSupport::renderer([]);
    $view = FormTestSupport::field('user_name', [
        'id' => 'user_name',
        'required' => true,
        'label_attr' => ['class' => 'wide'],
        'translation_domain' => false,
    ]);

    $formatted = $renderer->formLabel($view, null, ['label_format' => 'Field %name% %id%'])?->toString() ?? '';
    $html = $renderer->formLabel($view, '<strong>Name</strong>', [
        'label_html' => true,
        'translation_domain' => false,
    ])?->toString() ?? '';

    expect($renderer->formLabel($view, null, ['label' => false]))->toBeNull();
    expect($formatted)
        ->toContain('for="user_name"')
        ->toContain('class="required wide"')
        ->toContain('>Field user_name user_name</label>');
    expect($html)->toContain('<strong>Name</strong>');
});

test('formHelp returns null for empty values and renders explicit help ids', function () {
    $translator = new class implements TranslatorInterface {
        /**
         * @param array<string, mixed> $parameters
         */
        public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
        {
            return 'tr:' . $id;
        }

        public function getLocale(): string
        {
            return 'en';
        }
    };

    $renderer = FormTestSupport::renderer(translator: $translator);

    expect($renderer->formHelp(FormTestSupport::field('body')))->toBeNull();

    $html = $renderer->formHelp(FormTestSupport::field('body', [
        'id' => 'post_body',
        'help' => 'help.body',
        'help_html' => true,
        'help_id' => 'custom_help',
        'help_attr' => ['class' => 'muted'],
    ]))?->toString() ?? '';

    expect($html)
        ->toContain('class="help-text muted"')
        ->toContain('id="custom_help"')
        ->toContain('>tr:help.body</div>');
});

test('formErrors renders only form errors and formRest renders only unrendered children', function () {
    $renderer = FormTestSupport::renderer([]);
    $errorsView = FormTestSupport::field('email', [
        'errors' => new \ArrayIterator([new FormError('Bad email'), 'skip']),
    ]);

    expect($renderer->formErrors($errorsView)?->toString() ?? '')
        ->toBe('<ul id="email_error"><li>Bad email</li></ul>');

    $title = FormTestSupport::field('title');
    $done = FormTestSupport::field('done');
    $done->setRendered();

    $rest = $renderer->formRest(FormTestSupport::view([
        'id' => 'post',
        'name' => 'post',
        'full_name' => 'post',
        'compound' => true,
        'method' => 'PATCH',
    ], [
        'title' => $title,
        'done' => $done,
    ]))->toString();

    expect($rest)
        ->toContain('name="title"')
        ->toContain('<input type="hidden" name="_method" value="PATCH" />');

    expect(str_contains($rest, 'name="done"'))->toBeFalse();

    expect($title->isRendered())->toBeTrue();
});

test('formWidget delegates to resolved form elements and marks the view rendered', function () {
    $element = new class implements FormElementInterface {
        public ?FormRendererInterface $renderer = null;

        public function priority(): int
        {
            return 100;
        }

        public function supports(FormView $view): bool
        {
            return true;
        }

        public function render(FormView $view, FormRendererInterface $formRenderer, array $variables = []): Element
        {
            $this->renderer = $formRenderer;

            $message = $variables['message'] ?? '';
            if (!is_string($message)) {
                $message = '';
            }

            return div()->text($message);
        }
    };

    $renderer = FormTestSupport::renderer([$element]);
    $view = FormTestSupport::field('custom');

    expect($renderer->formWidget($view, ['message' => 'delegated'])?->toString() ?? '')
        ->toBe('<div>delegated</div>');
    expect($element->renderer)->toBe($renderer);
    expect($view->isRendered())->toBeTrue();
});
