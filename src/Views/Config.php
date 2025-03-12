<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Facades\Config as Configuration;
use Phenix\Util\Str;

class Config
{
    private array $config;

    public function __construct()
    {
        $this->config = Configuration::get('view', []);
    }

    public function path(string|null $path = null): string
    {
        return $this->buildPath($this->config['path'], $path);
    }

    public function compiledPath(string|null $path = null): string
    {
        return $this->buildPath($this->config['compiled_path'], $path);
    }

    private function buildPath(string $base, string|null $path = null): string
    {
        $path = $path ? Str::finish($path, '.php') : '';
    
        return Str::finish($base, DIRECTORY_SEPARATOR) . $path;
    }
}
