<?php

declare(strict_types=1);

namespace Tests\Unit\Locator;

use Berry\Symfony\Locator\AbstractServiceLocator;
use Override;
use stdClass;
use Symfony\Component\DependencyInjection\Container;

class FirstTestLocator extends AbstractServiceLocator
{
    #[Override]
    public static function services(): array
    {
        return [stdClass::class, 'some.id' => stdClass::class];
    }

    public static function get(string $id): mixed
    {
        return self::getService($id, 'some/package');
    }
}

class SecondTestLocator extends FirstTestLocator
{
}

test('each locator keeps its own container', function () {
    $first = new Container();
    $first->set('some.id', new stdClass());
    $second = new Container();

    new FirstTestLocator($first);
    new SecondTestLocator($second);

    expect(FirstTestLocator::get('some.id'))->toBeInstanceOf(stdClass::class);
    expect(fn() => SecondTestLocator::get('some.id'))->toThrow(\LogicException::class, 'composer require');
});
