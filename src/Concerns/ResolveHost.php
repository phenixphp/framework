<?php

declare(strict_types=1);

namespace Phenix\Concerns;

use League\Uri\Uri;
use Phenix\Exceptions\RuntimeException;
use Phenix\Facades\Config;

use function in_array;

trait ResolveHost
{
    protected function resolvePublicHost(): string
    {
        $url = (string) Config::get('app.url');
        $uri = Uri::new($url);
        $host = $uri->getHost();

        if (! in_array($uri->getScheme(), ['http', 'https'], true) || $host === '') {
            throw new RuntimeException('App URL must be an absolute HTTP or HTTPS URL with a host.');
        }

        return $host;
    }

    protected function resolveBindHost(): string
    {
        $host = trim((string) Config::get('app.host'));

        if ($host === '') {
            throw new RuntimeException('Bind host must not be empty.');
        }

        return trim($host, '[]');
    }

    protected function resolveBindPort(): int
    {
        $port = (int) Config::get('app.port');

        if ($port < 1 || $port > 65535) {
            throw new RuntimeException('Bind port must be between 1 and 65535.');
        }

        return $port;
    }
}
