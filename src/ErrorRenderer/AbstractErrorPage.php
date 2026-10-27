<?php

declare(strict_types=1);

namespace Berry\Symfony\ErrorRenderer;

use Berry\Symfony\View\AbstractComponent;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

abstract class AbstractErrorPage extends AbstractComponent
{
    final public function __construct(
        protected readonly FlattenException $exception,
    ) {
    }
}
