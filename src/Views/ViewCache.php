<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Facades\File;
use Phenix\Util\Str;

class ViewCache
{
    public function __construct(
        protected Config $config = new Config(),
    ) {
    }

    public function getViewPath(): string
    {
        return $this->config->path();
    }

    public function getSourcePath(string $template): string
    {
        return $this->config->path(ViewName::normalize($template));
    }

    public function getCacheFilePath(string $template): string
    {
        return $this->config->compiledPath(Str::finish($template, '.php'));
    }

    public function isCached(string $template): bool
    {
        $cacheFile = $this->getCacheFilePath($template);
        $sourceFile = $this->getSourcePath($template);

        return File::exists($cacheFile) && ! $this->isExpired($sourceFile, $cacheFile);
    }

    public function put(string $template, string $content): void
    {
        File::put($this->getCacheFilePath($template), $content);
    }

    private function isExpired(string $sourceFile, string $cacheFile): bool
    {
        return File::getModificationTime($sourceFile) > File::getModificationTime($cacheFile);
    }
}
