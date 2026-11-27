<?php

declare(strict_types=1);

namespace Phenix\Views;

use Amp\Cancellation;
use eftec\bladeone\BladeOne;
use Phenix\Facades\File;
use Phenix\Views\Exceptions\ViewNotFoundException;

use function func_num_args;

class BladeEngine extends BladeOne
{
    protected Cancellation|null $cancellation = null;

    public function __construct(protected TemplateCache $cache)
    {
        parent::__construct($cache->getViewPath(), (new ViewsConfig())->compiledPath(), self::MODE_AUTO);

        $this->setFileExtension('.php');
        $this->echoFormat = 'e(%s)';
    }

    public function setCancellation(Cancellation|null $cancellation): void
    {
        $this->cancellation = $cancellation;
    }

    public function getTemplateFile(string|null $templateName = ''): string
    {
        $templateName = $templateName ?: $this->fileName;
        $file = File::realPath($this->cache->getSourcePath($templateName, $this->cancellation), $this->cancellation);
        $base = File::realPath($this->cache->getViewPath($templateName, $this->cancellation), $this->cancellation);

        if ($this->isInvalidTemplatePath($file, $base)) {
            return '';
        }

        return $file;
    }

    public function getCompiledFile(string|null $templateName = ''): string
    {
        $templateName = $templateName ?: $this->fileName;

        if ($this->getTemplateFile($templateName) === '') {
            throw new ViewNotFoundException("Template {$templateName} not found.");
        }

        return $this->cache->getCacheFilePath($templateName);
    }

    public function compile(string|null $templateName = null, bool $forced = false): bool
    {
        $templateName = $templateName ?: $this->fileName;
        $compiled = $this->getCompiledFile($templateName);
        $source = $this->getTemplateFile($templateName);

        if (! $forced && ! $this->isCompiledExpired($compiled, $source)) {
            return true;
        }

        $contents = $this->compileString($this->getFile($source));
        $this->compileCallBacks($contents, $templateName);
        $directory = dirname($compiled);
        $temporary = $directory . DIRECTORY_SEPARATOR . 'view-' . bin2hex(random_bytes(16));
        $created = false;

        try {
            $file = File::openFile($temporary, 'x');
            $created = true;

            try {
                $file->write($contents);
            } finally {
                $file->close();
            }

            File::move($temporary, $compiled);
            $created = false;
        } finally {
            if ($created) {
                File::deleteFile($temporary);
            }
        }

        return true;
    }

    public function getFile(string $fullFileName): string
    {
        return File::get($fullFileName);
    }

    public function isExpired(string|null $fileName): bool
    {
        return $this->isCompiledExpired($this->getCompiledFile($fileName), $this->getTemplateFile($fileName));
    }

    protected function templateExist(mixed $templateName): bool
    {
        $path = $this->getTemplateFile($templateName);
        $status = $path === '' ? null : File::getStatus($path, $this->cancellation);

        return $status !== null && ($status['mode'] & 0170000) === 0100000;
    }

    protected function evaluatePath(string $compiledFile, array $variables): string
    {
        return $this->evaluateText($this->getFile($compiledFile), $variables);
    }

    protected function compileIncludeFast(mixed $expression): string
    {
        $expression = $this->stripParentheses($expression);
        $parts = explode(',', $expression);
        $name = $this->stripQuotes($parts[0] ?? null);
        $path = $this->getCompiledFile($name);

        if (! File::isFile($path)) {
            $this->compile($name, true);
        }

        return $this->getFile($path);
    }

    public function startSection(mixed $section, mixed $content = ''): void
    {
        if (func_num_args() === 1) {
            parent::startSection($section);

            return;
        }

        $this->extendSection($section, e($content));
    }

    /**
     * @phpstan-assert-if-false string $file
     * @phpstan-assert-if-false string $base
     */
    private function isInvalidTemplatePath(string|false $file, string|false $base): bool
    {
        return $file === false || $base === false || ! str_starts_with($file, rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
    }

    private function isCompiledExpired(string $compiled, string $source): bool
    {
        $compiledStatus = File::getStatus($compiled, $this->cancellation);
        $sourceStatus = File::getStatus($source, $this->cancellation);

        return $compiledStatus === null || ($compiledStatus['mode'] & 0170000) !== 0100000
            || $sourceStatus === null || $compiledStatus['mtime'] < $sourceStatus['mtime'];
    }
}
