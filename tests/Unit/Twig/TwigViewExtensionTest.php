<?php

declare(strict_types=1);

use Berry\Element;
use Berry\Symfony\Twig\TwigViewExtension;
use Berry\Symfony\View\AbstractComponent;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

use function Berry\Html\h2;

final class TwigViewExtensionTestHelloWorld extends AbstractComponent
{
    public function __construct(
        private readonly ?string $name = null,
    ) {
    }

    #[Override]
    public function renderComponent(): Element
    {
        $name = $this->name ?? 'World';

        return h2()->text("Hello, {$name}!");
    }
}

final class TwigViewExtensionTestDependency
{
    public function value(): string
    {
        return 'dependency';
    }
}

final class TwigViewExtensionTestNamedArguments extends AbstractComponent
{
    public function __construct(
        private readonly string $firstName = 'World',
        private readonly ?string $lastName = 'Default',
    ) {
    }

    #[Override]
    public function renderComponent(): Element
    {
        return h2()->text($this->firstName . ':' . ($this->lastName ?? 'null'));
    }
}

final class TwigViewExtensionTestWithoutConstructor extends AbstractComponent
{
    #[Override]
    public function renderComponent(): Element
    {
        return h2()->text('Hello');
    }
}

final class TwigViewExtensionTestExactNames extends AbstractComponent
{
    public function __construct(
        private readonly string $firstName = 'camel',
        private readonly string $first_name = 'snake',
        private readonly string $userID = 'ID',
    ) {
    }

    #[Override]
    public function renderComponent(): Element
    {
        return h2()->text($this->firstName . ':' . $this->first_name . ':' . $this->userID);
    }
}

final class TwigViewExtensionTestLegacyArguments extends AbstractComponent
{
    /** @param string|null $suffix */
    public function __construct(
        private readonly string $firstName,
        private readonly TwigViewExtensionTestDependency $dependency,
        private readonly ?string $lastName,
        private $suffix,
    ) {
    }

    #[Override]
    public function renderComponent(): Element
    {
        return h2()->text(
            $this->dependency->value()
                . ':'
                . $this->firstName
                . ':'
                . ($this->lastName ?? 'null')
                . ($this->suffix ?? ''),
        );
    }
}

final class TwigViewExtensionTestWithDependency extends AbstractComponent
{
    public function __construct(
        private readonly TwigViewExtensionTestDependency $dependency,
        private readonly string $name,
    ) {
    }

    #[Override]
    public function renderComponent(): Element
    {
        return h2()->text($this->dependency->value() . ':' . $this->name);
    }
}

test('TwigViewExtension renders registered component functions', function () {
    $extension = new TwigViewExtension([
        'hello_world' => TwigViewExtensionTestHelloWorld::class,
    ]);

    $function = $extension->getFunctions()[0];
    $callable = $function->getCallable();

    if (!is_callable($callable)) {
        throw new LogicException('Twig function is not callable.');
    }

    expect($function->getName())->toBe('hello_world');
    expect($callable())->toBe('<h2>Hello, World!</h2>');
    expect($callable('Peter'))->toBe('<h2>Hello, Peter!</h2>');
});

test('TwigViewExtension marks component output as safe html', function () {
    $twig = new Environment(
        new ArrayLoader([
            'template' => '{{ hello_world("<Peter>") }}',
        ]),
        [
            'autoescape' => 'html',
        ],
    );

    $twig->addExtension(new TwigViewExtension([
        'hello_world' => TwigViewExtensionTestHelloWorld::class,
    ]));

    expect($twig->render('template'))->toBe('<h2>Hello, &lt;Peter&gt;!</h2>');
});

test('TwigViewExtension injects services and passes runtime arguments', function () {
    $extension = new TwigViewExtension(
        [
            'with_dependency' => TwigViewExtensionTestWithDependency::class,
        ],
        new ServiceLocator([
            TwigViewExtensionTestDependency::class => fn() => new TwigViewExtensionTestDependency(),
        ]),
    );

    $function = $extension->getFunctions()[0];
    $callable = $function->getCallable();

    if (!is_callable($callable)) {
        throw new LogicException('Twig function is not callable.');
    }

    expect($function->getName())->toBe('with_dependency');
    expect($callable('Peter'))->toBe('<h2>dependency:Peter</h2>');
});

test('TwigViewExtension resolves arguments through Twig', function (string $template, string $expected) {
    $twig = new Environment(new ArrayLoader(['template' => $template]), ['autoescape' => 'html']);
    $twig->addExtension(new TwigViewExtension(
        [
            'greeter' => TwigViewExtensionTestHelloWorld::class,
            'names' => TwigViewExtensionTestNamedArguments::class,
            'without_constructor' => TwigViewExtensionTestWithoutConstructor::class,
            'legacy' => TwigViewExtensionTestLegacyArguments::class,
            'exact_names' => TwigViewExtensionTestExactNames::class,
        ],
        new ServiceLocator([
            TwigViewExtensionTestDependency::class => fn() => new TwigViewExtensionTestDependency(),
        ]),
    ));

    expect($twig->render('template'))->toBe($expected);
})->with([
    'named argument' => ['{{ greeter(name: "Hello") }}', '<h2>Hello, Hello!</h2>'],
    'escaped named argument' => ['{{ greeter(name: "<Peter>") }}', '<h2>Hello, &lt;Peter&gt;!</h2>'],
    'ignored extra positional argument' => ['{{ greeter("Peter", "ignored") }}', '<h2>Hello, Peter!</h2>'],
    'positional null' => ['{{ greeter(null) }}', '<h2>Hello, World!</h2>'],
    'positional argument unpacking' => ['{{ greeter(...["Peter"]) }}', '<h2>Hello, Peter!</h2>'],
    'default argument' => ['{{ greeter() }}', '<h2>Hello, World!</h2>'],
    'out of order' => ['{{ names(lastName: "Pan", firstName: "Peter") }}', '<h2>Peter:Pan</h2>'],
    'skipped default' => ['{{ names(lastName: "Pan") }}', '<h2>World:Pan</h2>'],
    'explicit null' => ['{{ names(lastName: null) }}', '<h2>World:null</h2>'],
    'mixed arguments' => ['{{ names("Peter", lastName: "Pan") }}', '<h2>Peter:Pan</h2>'],
    'distinct camelCase and snake_case names' => [
        '{{ exact_names(firstName: "Peter", first_name: "Pan", userID: "42") }}',
        '<h2>Peter:Pan:42</h2>',
    ],
    'ignored positional arguments without constructor' => [
        '{{ without_constructor("ignored", null) }}',
        '<h2>Hello</h2>',
    ],
    'implicit nullable and untyped arguments' => ['{{ legacy("Peter") }}', '<h2>dependency:Peter:null</h2>'],
    'ignored extra positional argument with service' => [
        '{{ legacy("Peter", "Pan", null, "ignored") }}',
        '<h2>dependency:Peter:Pan</h2>',
    ],
]);

test('TwigViewExtension rejects invalid arguments through Twig', function (string $template, string $message) {
    $twig = new Environment(new ArrayLoader(['template' => $template]));
    $twig->addExtension(new TwigViewExtension(
        [
            'greeter' => TwigViewExtensionTestHelloWorld::class,
            'names' => TwigViewExtensionTestNamedArguments::class,
            'with_dependency' => TwigViewExtensionTestWithDependency::class,
            'without_constructor' => TwigViewExtensionTestWithoutConstructor::class,
        ],
        new ServiceLocator([
            TwigViewExtensionTestDependency::class => fn() => new TwigViewExtensionTestDependency(),
        ]),
    ));

    expect(fn() => $twig->render('template'))->toThrow(\Twig\Error\RuntimeError::class, $message);
})->with([
    'unknown name' => ['{{ greeter(typo: "Peter") }}', 'Unknown argument "typo"'],
    'no snake_case conversion' => ['{{ names(first_name: "Peter") }}', 'Unknown argument "first_name"'],
    'case-sensitive names' => ['{{ names(firstname: "Peter") }}', 'Unknown argument "firstname"'],
    'duplicate argument' => ['{{ greeter("Peter", name: "Pan") }}', 'Argument "$name" is defined twice'],
    'missing required argument' => ['{{ with_dependency() }}', 'Missing argument "$name"'],
    'cannot override service' => [
        '{{ with_dependency(name: "Peter", dependency: null) }}',
        'Unknown argument "dependency"',
    ],
    'argument without constructor' => ['{{ without_constructor(name: "Peter") }}', 'Unknown argument "name"'],
]);

test('TwigViewExtension rejects positional arguments after named arguments', function () {
    $twig = new Environment(new ArrayLoader(['template' => '{{ names(firstName: "Peter", "Pan") }}']));
    $twig->addExtension(new TwigViewExtension([
        'names' => TwigViewExtensionTestNamedArguments::class,
    ]));

    expect(fn() => $twig->render('template'))
        ->toThrow(\Twig\Error\SyntaxError::class, 'Positional arguments cannot be used after named arguments');
});
