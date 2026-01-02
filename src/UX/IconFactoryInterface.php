<?php declare(strict_types=1);

namespace Berry\Symfony\UX;

use Berry\Element;
use Symfony\UX\Icons\Exception\IconNotFoundException;

interface IconFactoryInterface
{
    /**
     * @param array<string, string|bool> $attributes an array of HTML attributes
     *
     * @throws IconNotFoundException
     */
    public function render(string $name, array $attributes = []): Element;
}
