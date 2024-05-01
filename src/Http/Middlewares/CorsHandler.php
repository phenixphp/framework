<?php

declare(strict_types=1);

namespace Phenix\Http\Middlewares;

use Phenix\Facades\Config;
use Cspray\Labrador\Http\Cors\CorsMiddleware;
use Cspray\Labrador\Http\Cors\ArrayConfiguration;
use Cspray\Labrador\Http\Cors\SimpleConfigurationLoader;

class CorsHandler extends CorsMiddleware
{
    public function __construct()
    {
        /** @var array $cors */
        $cors = Config::get('cors');

        $config = new ArrayConfiguration($cors);
        $loader = new SimpleConfigurationLoader($config);

        parent::__construct($loader);
    }
}
