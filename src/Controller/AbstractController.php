<?php declare(strict_types=1);

namespace Berry\Symfony\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;

class AbstractController extends SymfonyAbstractController
{
    use BerryControllerTrait;
}
