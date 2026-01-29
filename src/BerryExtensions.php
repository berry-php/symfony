<?php declare(strict_types=1);

namespace Berry\Symfony;

use Berry\Html\HtmlTag;
use Berry\Html\HtmlVoidTag;
use Berry\Symfony\Locator\ComponentServiceLocator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Closure;

final class BerryExtensions
{
    public static function install(bool $enableVarDumper = true): void
    {
        HtmlTag::addMethod('toResponse', static::toResponse());
        HtmlVoidTag::addMethod('toResponse', static::toResponse());

        HtmlTag::addMethod('trans', static::trans());

        if ($enableVarDumper) {
            HtmlTag::addMethod('dump', static::dump());
        }
    }

    private static function toResponse(): Closure
    {
        return function (HtmlTag|HtmlVoidTag $node, int $status = 200, array $headers = []): Response {
            return new Response($node->toString(), $status, $headers);
        };
    }

    private static function trans(): Closure
    {
        return function (HtmlTag $node, string $id, array $parameters = [], ?string $domain = null, ?string $locale = null, ?TranslatorInterface $translator = null, ?bool $escape = true): HtmlTag {
            $text = ($translator ?? ComponentServiceLocator::getTranslator())->trans($id, $parameters, $domain, $locale);

            if (!$escape) {
                return $node->unsafeRaw($text);
            }

            return $node->text($text);
        };
    }

    private static function dump(): Closure
    {
        return function (HtmlTag $node, mixed ...$vars): HtmlTag {
            $dumper = new HtmlDumper();
            $cloner = new VarCloner();

            if (count($vars) === 0) {
                return $node;
            }

            if (array_key_exists(0, $vars) && count($vars) === 1) {
                return $node->unsafeRaw($dumper->dump($cloner->cloneVar($vars[0]), true));
            }

            foreach ($vars as $var) {
                $node->unsafeRaw($dumper->dump($cloner->cloneVar($var), true));
            }

            return $node;
        };
    }
}
