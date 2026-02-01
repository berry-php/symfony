<?php declare(strict_types=1);

use Berry\Symfony\BerryExtensions;
use Symfony\Component\VarDumper\VarDumper;

(function () {
    static $initialized = false;

    if ($initialized) {
        return;
    }

    BerryExtensions::install(class_exists(VarDumper::class));

    $initialized = true;
})();
