<?php

declare(strict_types=1);

namespace Berry\Symfony\Twig\Node;

use Override;
use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\FunctionExpression;

final class TwigViewFunctionExpression extends FunctionExpression
{
    #[Override]
    protected function compileArguments(Compiler $compiler, mixed $isArray = false): void
    {
        $compiler->raw('(...[');

        $named = false;

        foreach ($this->getNode('arguments') as $name => $argument) {
            if (is_int($name) && $named) {
                throw new SyntaxError(
                    'Positional arguments cannot be used after named arguments for Twig views.',
                    $this->getTemplateLine(),
                    $this->getSourceContext(),
                );
            }

            if (is_string($name)) {
                $named = true;
                $compiler->string($name)->raw(' => ');
            }

            $compiler->subcompile($argument)->raw(', ');
        }

        $compiler->raw('])');
    }
}
