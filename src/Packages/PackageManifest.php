<?php

declare(strict_types=1);

namespace Phenix\Packages;

use JsonException;
use Phenix\Exceptions\RuntimeError;
use Phenix\Util\Path;
use RuntimeException;

use function in_array;
use function is_array;
use function is_string;

class PackageManifest
{
    private array|null $manifest = null;

    public function __construct(
        private string $basePath,
        private string|null $manifestPath = null,
        private string|null $vendorPath = null,
    ) {
        $this->manifestPath ??= Path::join($basePath, 'storage', 'framework', 'cache', 'packages.php');
        $this->vendorPath ??= getenv('COMPOSER_VENDOR_DIR') ?: Path::join($basePath, 'vendor');
    }

    public function providers(): array
    {
        $providers = [];

        foreach ($this->getManifest() as $configuration) {
            foreach ((array) ($configuration['providers'] ?? []) as $provider) {
                if (is_string($provider) && $provider !== '') {
                    $providers[] = $provider;
                }
            }
        }

        return array_values(array_unique($providers));
    }

    public function packages(): array
    {
        return array_keys($this->getManifest());
    }

    public function getManifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        if (! is_file($this->manifestPath)) {
            $this->build();
        }

        if (! is_file($this->manifestPath)) {
            return $this->manifest = [];
        }

        $manifest = require $this->manifestPath;

        return $this->manifest = is_array($manifest) ? $manifest : [];
    }

    public function build(): void
    {
        $packages = $this->installedPackages();
        $ignored = $this->packagesToIgnore();
        $ignoreAll = in_array('*', $ignored, true);
        $manifest = [];

        foreach ($packages as $package) {
            if (! is_array($package) || ! isset($package['name']) || ! is_string($package['name'])) {
                continue;
            }

            $configuration = $package['extra']['phenix'] ?? [];

            if (! is_array($configuration)) {
                continue;
            }

            foreach ((array) ($configuration['dont-discover'] ?? []) as $packageToIgnore) {
                if (is_string($packageToIgnore)) {
                    $ignored[] = $packageToIgnore;
                }
            }

            if ($configuration !== []) {
                $manifest[$package['name']] = $configuration;
            }
        }

        $manifest = array_filter(
            $manifest,
            static fn (string $package): bool => ! $ignoreAll && ! in_array($package, $ignored, true),
            ARRAY_FILTER_USE_KEY,
        );

        $this->write($manifest);
        $this->manifest = $manifest;
    }

    public function clear(): void
    {
        $this->manifest = null;

        if (is_file($this->manifestPath)) {
            unlink($this->manifestPath);
        }
    }

    public function path(): string
    {
        return $this->manifestPath;
    }

    private function installedPackages(): array
    {
        $path = Path::join($this->vendorPath, 'composer', 'installed.json');

        if (! is_file($path)) {
            return [];
        }

        try {
            $installed = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeError("Unable to read Composer package metadata at {$path}.", 0, $exception);
        }

        if (! is_array($installed)) {
            return [];
        }

        $packages = $installed['packages'] ?? $installed;

        return is_array($packages) ? $packages : [];
    }

    private function packagesToIgnore(): array
    {
        $path = Path::join($this->basePath, 'composer.json');

        if (! is_file($path)) {
            return [];
        }

        try {
            $composer = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeError("Unable to read the application Composer file at {$path}.", 0, $exception);
        }

        $ignored = $composer['extra']['phenix']['dont-discover'] ?? [];

        return is_array($ignored) ? $ignored : [];
    }

    private function write(array $manifest): void
    {
        $directory = dirname($this->manifestPath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeError("The package cache directory {$directory} could not be created.");
        }

        if (! is_writable($directory)) {
            throw new RuntimeError("The package cache directory {$directory} must be writable.");
        }

        $temporaryPath = tempnam($directory, 'packages-');

        if ($temporaryPath === false) {
            throw new RuntimeError("Unable to create a temporary package manifest in {$directory}.");
        }

        $contents = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($manifest, true) . ';' . PHP_EOL;

        if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false || ! rename($temporaryPath, $this->manifestPath)) {
            @unlink($temporaryPath);

            throw new RuntimeError("Unable to write the package manifest at {$this->manifestPath}.");
        }
    }
}
