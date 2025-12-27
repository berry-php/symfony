<?php declare(strict_types=1);

namespace Berry\Symfony\View;

use Berry\Renderable;

abstract class AbstractView implements Renderable
{
    abstract public function render(): Renderable;

    public function toString(): string
    {
        return $this->render()->toString();
    }

    public function renderInto(array &$buffer): void
    {
        $this->render()->renderInto($buffer);
    }
}
