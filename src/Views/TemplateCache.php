<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Facades\File;
use Throwable;

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

    public function isCached(string $template): bool
    {
        $cacheFile = $this->getCacheFilePath($template);
        $sourceFile = $this->getSourcePath($template);

        $compiled = File::getStatus($cacheFile);
        $source = File::getStatus($sourceFile);

        return $compiled !== null && ($compiled['mode'] & 0170000) === 0100000
            && $source !== null && $compiled['mtime'] >= $source['mtime'];
    }

    public function put(string $template, string $content): void
    {
        $compiled = $this->getCacheFilePath($template);
        $temporary = dirname($compiled) . DIRECTORY_SEPARATOR . 'view-' . bin2hex(random_bytes(16));
        $file = File::openFile($temporary, 'x');

        try {
            try {
                $file->write($content);
            } finally {
                $file->close();
            }

            File::move($temporary, $compiled);
        } catch (Throwable $exception) {
            File::deleteFile($temporary);

            throw $exception;
        }
    }

    public function clear(): void
    {
        foreach (File::listFiles($this->config->compiledPath()) as $file) {
            if (str_ends_with($file, '.php')) {
                File::deleteFile($file);
            }
        }
    }

    protected function isExpired(string $sourceFile, string $cacheFile): bool
    {
        return File::getModificationTime($sourceFile) > File::getModificationTime($cacheFile);
    }
}
