<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Facades\File;
use Phenix\Util\Str;

class ViewCache
{
    public function __construct(
        protected string $cachePath
    ) {
    }

    public function getCacheFilePath(string $template): string
    {
        return Str::finish($this->cachePath, DIRECTORY_SEPARATOR) . md5($template) . '.php';
    }

    public function isCached(string $template): bool
    {
        $file = $this->getCacheFilePath($template);

        return File::exists($file);
    }

    public function put(string $template, string $content): void
    {
        File::put($this->getCacheFilePath($template), $content);
    }

    public function get(string $template): string
    {
        return File::get($this->getCacheFilePath($template));
    }
}
