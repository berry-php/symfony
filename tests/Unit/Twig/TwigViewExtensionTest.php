<?php declare(strict_types=1);

use Berry\Element;
use Berry\Symfony\Twig\TwigViewExtension;
use Berry\Symfony\View\AbstractComponent;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

use function Berry\Html\h2;

final class TwigViewExtensionTestHelloWorld extends AbstractComponent
{
    public function __construct(private readonly ?string $name = null) {}

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

final class TwigViewExtensionTestWithDependency extends AbstractComponent
{
    public function __construct(
        private readonly TwigViewExtensionTestDependency $dependency,
        private readonly string $name,
    ) {}

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
    $twig = new Environment(new ArrayLoader([
        'template' => '{{ hello_world("<Peter>") }}',
    ]), [
        'autoescape' => 'html',
    ]);

    $twig->addExtension(new TwigViewExtension([
        'hello_world' => TwigViewExtensionTestHelloWorld::class,
    ]));

    expect($twig->render('template'))->toBe('<h2>Hello, &lt;Peter&gt;!</h2>');
});

test('TwigViewExtension injects services and passes runtime arguments', function () {
    $extension = new TwigViewExtension([
        'with_dependency' => TwigViewExtensionTestWithDependency::class,
    ], new ServiceLocator([
        TwigViewExtensionTestDependency::class => fn() => new TwigViewExtensionTestDependency(),
    ]));

    $function = $extension->getFunctions()[0];
    $callable = $function->getCallable();

    if (!is_callable($callable)) {
        throw new LogicException('Twig function is not callable.');
    }

    expect($function->getName())->toBe('with_dependency');
    expect($callable('Peter'))->toBe('<h2>dependency:Peter</h2>');
});
