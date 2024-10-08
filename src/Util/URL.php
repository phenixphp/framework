<?php

declare(strict_types=1);

namespace Phenix\Util;

use Phenix\Facades\Config;

class URL extends Utility
{
    public static function build(string $path, array $parameters = []): string
    {
        $path = trim($path, '/');

        $port = Config::get('app.port');

        $url = Config::get('app.url');

        $uri = "{$url}:{$port}/{$path}";

        if (! empty($parameters)) {
            $uri .= '?' . http_build_query($parameters);
        }

        return $uri;
    }
}
