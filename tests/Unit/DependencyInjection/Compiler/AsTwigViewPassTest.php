<?php

declare(strict_types=1);

use Berry\Element;
use Berry\Symfony\BerryBundle;
use Berry\Symfony\DependencyInjection\Compiler\AsTwigViewPass;
use Berry\Symfony\Twig\TwigViewExtension;
use Berry\Symfony\View\AbstractComponent;
use Berry\Symfony\View\Attribute\AsTwigView;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

use function Berry\Html\div;

final class AsTwigViewPassTestComponent extends AbstractComponent
{
    #[Override]
    public function renderComponent(): Element
    {
        return div();
    }
}

final class AsTwigViewPassTestDuplicateComponent extends AbstractComponent
{
    #[Override]
    public function renderComponent(): Element
    {
        return div();
    }
}

final class AsTwigViewPassTestDependency
{
    public function value(): string
    {
        return 'dependency';
    }
}

#[AsTwigView(name: 'from_attribute')]
final class AsTwigViewPassTestAttributedComponent extends AbstractComponent
{
    public function __construct(
        private readonly string $name,
    ) {
    }

    #[Override]
    public function renderComponent(): Element
    {
        return div()->text($this->name);
    }
}

#[AsTwigView(name: 'with_dependency')]
final class AsTwigViewPassTestAttributedComponentWithDependency extends AbstractComponent
{
    public function __construct(
        private readonly AsTwigViewPassTestDependency $dependency,
        private readonly string $name,
    ) {
    }

    #[Override]
    public function renderComponent(): Element
    {
        return div()->text($this->dependency->value() . ':' . $this->name);
    }
}

test('AsTwigViewPass registers tagged components on Twig extension', function () {
    $container = new ContainerBuilder();

    $container->register(TwigViewExtension::class, TwigViewExtension::class);
    $container
        ->register(AsTwigViewPassTestComponent::class, AsTwigViewPassTestComponent::class)
        ->addTag(AsTwigViewPass::TAG, ['name' => 'hello_world']);

    (new AsTwigViewPass())->process($container);

    expect($container->getDefinition(TwigViewExtension::class)->getArgument(0))->toBe([
        'hello_world' => AsTwigViewPassTestComponent::class,
    ]);
});

test('AsTwigView attribute autoconfigures component as Twig function', function () {
    $container = new ContainerBuilder();

    (new BerryBundle())->build($container);

    $container->register(TwigViewExtension::class, TwigViewExtension::class)->setPublic(true);
    $container
        ->register(AsTwigViewPassTestAttributedComponent::class, AsTwigViewPassTestAttributedComponent::class)
        ->setAutoconfigured(true)
        ->setAutowired(true);

    $container->compile();

    $extension = $container->get(TwigViewExtension::class);

    if (!$extension instanceof TwigViewExtension) {
        throw new LogicException('Twig view extension was not registered.');
    }

    $function = $extension->getFunctions()[0];
    $callable = $function->getCallable();

    if (!is_callable($callable)) {
        throw new LogicException('Twig function is not callable.');
    }

    expect($function->getName())->toBe('from_attribute');
    expect($callable('Peter'))->toBe('<div>Peter</div>');
});

test('AsTwigView attribute supports constructor services and Twig arguments', function () {
    $container = new ContainerBuilder();

    (new BerryBundle())->build($container);

    $container->register(TwigViewExtension::class, TwigViewExtension::class)->setPublic(true);
    $container->register(AsTwigViewPassTestDependency::class, AsTwigViewPassTestDependency::class);
    $container
        ->register(
            AsTwigViewPassTestAttributedComponentWithDependency::class,
            AsTwigViewPassTestAttributedComponentWithDependency::class,
        )
        ->setAutoconfigured(true)
        ->setAutowired(true);

    $container->compile();

    $extension = $container->get(TwigViewExtension::class);

    if (!$extension instanceof TwigViewExtension) {
        throw new LogicException('Twig view extension was not registered.');
    }

    $function = $extension->getFunctions()[0];
    $callable = $function->getCallable();

    if (!is_callable($callable)) {
        throw new LogicException('Twig function is not callable.');
    }

    expect($function->getName())->toBe('with_dependency');
    expect($callable('Peter'))->toBe('<div>dependency:Peter</div>');

    $twig = new Environment(new ArrayLoader(['template' => '{{ with_dependency(name: "Peter") }}']));
    $twig->addExtension($extension);

    expect($twig->render('template'))->toBe('<div>dependency:Peter</div>');
});

test('AsTwigViewPass rejects duplicate Twig view names', function () {
    $container = new ContainerBuilder();

    $container->register(TwigViewExtension::class, TwigViewExtension::class);
    $container
        ->register(AsTwigViewPassTestComponent::class, AsTwigViewPassTestComponent::class)
        ->addTag(AsTwigViewPass::TAG, ['name' => 'hello_world']);
    $container
        ->register(AsTwigViewPassTestDuplicateComponent::class, AsTwigViewPassTestDuplicateComponent::class)
        ->addTag(AsTwigViewPass::TAG, ['name' => 'hello_world']);

    expect(fn() => (new AsTwigViewPass())->process($container))
        ->toThrow(LogicException::class, sprintf(
            'Twig view "hello_world" is already registered for class "%s" and cannot also be registered for class "%s".',
            AsTwigViewPassTestComponent::class,
            AsTwigViewPassTestDuplicateComponent::class,
        ));
});
