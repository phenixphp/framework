<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Facades\File;

class TemplateCache
{
    public function __construct(
        protected ViewsConfig $config = new ViewsConfig(),
    ) {
    }

    public function getViewPath(string|null $template = null): string
    {
        return $template === null ? $this->config->path() : $this->config->sourceBasePath($template);
    }

    public function getSourcePath(string $template): string
    {
        return $this->config->sourcePath($template);
    }

    public function getCacheFilePath(string $template): string
    {
        return $this->config->cachePath($template);
    }

    public function clear(): void
    {
        foreach (File::listFiles($this->config->compiledPath()) as $file) {
            if (str_ends_with($file, '.php')) {
                File::deleteFile($file);
            }
        }
    }
}
