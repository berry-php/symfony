<?php declare(strict_types=1);

namespace Berry\Symfony\Form\Elements;

use Berry\Symfony\Form\FormElementInterface;
use Berry\Symfony\Form\WithFormRenderUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractFormElement implements FormElementInterface
{
    use WithFormRenderUtils;

    public function __construct(?TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    public function priority(): int
    {
        return 0;
    }
}
