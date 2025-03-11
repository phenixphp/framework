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

    public function path(): string
    {
        return Str::finish($this->config['path'], DIRECTORY_SEPARATOR);
    }

    public function compiledPath(): string
    {
        return Str::finish($this->config['compiled_path'], DIRECTORY_SEPARATOR);
    }
}
