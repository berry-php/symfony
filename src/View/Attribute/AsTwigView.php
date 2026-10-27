<?php

declare(strict_types=1);

namespace Berry\Symfony\View\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsTwigView
{
    public function __construct(
        public string $name,
    ) {
    }
}
