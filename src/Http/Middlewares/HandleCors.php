<?php

declare(strict_types=1);

namespace Phenix\Http\Middlewares;

use Cspray\Labrador\Http\Cors\ArrayConfiguration;
use Cspray\Labrador\Http\Cors\CorsMiddleware;
use Cspray\Labrador\Http\Cors\SimpleConfigurationLoader;
use Phenix\Configurations\Cors;

class HandleCors extends CorsMiddleware
{
    public function __construct()
    {
        $cors = Cors::build();

        $config = new ArrayConfiguration($cors->toArray());
        $loader = new SimpleConfigurationLoader($config);

        parent::__construct($loader);
    }
}
