<?php declare(strict_types=1);

namespace Berry\Symfony\Form;

use Berry\Symfony\Locator\Trait\WithFormRendering;
use Berry\Symfony\View\AbstractComponent;

abstract class AbstractForm extends AbstractComponent
{
    use WithFormRendering;
}
