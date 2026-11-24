<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\App;
use Phenix\Facades\Config as Configuration;
use Phenix\Packages\PackageRegistry;
use Phenix\Util\Path;
use Phenix\Util\Str;

class ViewsConfig
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

    public function sourcePath(string $template): string
    {
        [$namespace, $name] = $this->parse($template);
        $relativePath = ViewName::ensure($name);

        if ($namespace === null) {
            return $this->path($relativePath);
        }

        return $this->buildPath($this->resolveNamespacedBasePath($namespace, $relativePath), $relativePath);
    }

    public function sourceBasePath(string $template): string
    {
        [$namespace, $name] = $this->parse($template);

        if ($namespace === null) {
            return $this->path();
        }

        return $this->resolveNamespacedBasePath($namespace, ViewName::ensure($name));
    }

    public function cachePath(string $template): string
    {
        return $this->compiledPath(hash('sha256', $template) . '.php');
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

    private function resolveNamespacedBasePath(string $namespace, string $relativePath): string
    {
        $override = $this->path(Path::join('vendor', $namespace));

        if (is_file($this->buildPath($override, $relativePath))) {
            return $override;
        }

        /** @var PackageRegistry $packages */
        $packages = App::make(PackageRegistry::class);

        foreach ($packages->viewPaths($namespace) as $path) {
            if (is_file($this->buildPath($path, $relativePath))) {
                return $path;
            }
        }

        return $override;
    }

    private function parse(string $template): array
    {
        if (! str_contains($template, '::')) {
            return [null, $template];
        }

        [$namespace, $name] = explode('::', $template, 2);

        return [ViewName::clean($namespace), $name];
    }
}
