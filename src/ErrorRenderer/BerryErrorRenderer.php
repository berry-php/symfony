<?php

declare(strict_types=1);

namespace Berry\Symfony\ErrorRenderer;

use Closure;
use Override;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Throwable;

final readonly class BerryErrorRenderer implements ErrorRendererInterface
{
    /**
     * @param class-string<AbstractErrorPage> $component
     */
    public function __construct(
        private ErrorRendererInterface $inner,
        private string $component,
        private Closure $debug,
        private string $charset = 'UTF-8',
    ) {
    }

    #[Override]
    public function render(Throwable $exception): FlattenException
    {
        if (($this->debug)()) {
            return $this->inner->render($exception);
        }

        $flattenException = FlattenException::createFromThrowable($exception, null, [
            'Content-Type' => "text/html; charset={$this->charset}",
        ]);

        return $flattenException->setAsString((new $this->component($flattenException))->toString());
    }
}
