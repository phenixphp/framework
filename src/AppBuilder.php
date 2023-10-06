<?php

declare(strict_types=1);

namespace Phenix;

use Phenix\Contracts\Buildable;
use Phenix\Runtime\Environment;

class AppBuilder implements Buildable
{
    public static function build(string|null $path = null, string|null $env = null): AppProxy
    {
        $app = new App($path ?? dirname(__DIR__));

        Environment::load($env);

        $app->setup();

        return new AppProxy($app);
    }
}
