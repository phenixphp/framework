<?php

declare(strict_types=1);

namespace Phenix;

use Phenix\Contracts\App as AppContract;
use Phenix\Contracts\Buildable;
use Phenix\Runtime\Environment;

class AppBuilder implements Buildable
{
    public static function build(): AppContract
    {
        $app = new App(dirname(__DIR__));

        Environment::load();

        $app->setup();

        return new AppProxy($app);
    }
}
