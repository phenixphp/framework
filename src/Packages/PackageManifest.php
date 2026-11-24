<?php

declare(strict_types=1);

namespace Phenix\Packages;

use JsonException;
use Throwable;
use Phenix\Exceptions\RuntimeException;
use Phenix\Util\Path;

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

        $cached = null;

        if (is_file($this->manifestPath)) {
            try {
                $cached = require $this->manifestPath;
            } catch (Throwable) {
                $cached = null;
            }
        }

        if ($this->needsRebuild($cached)) {
            return $this->build();
        }

        return $this->manifest = $cached['packages'];
    }

    public function build(): array
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

        $this->write(['fingerprint' => $this->fingerprint(), 'packages' => $manifest]);
        $this->manifest = $manifest;

        return $manifest;
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

    /**
     * @phpstan-assert-if-false array{fingerprint: string, packages: array} $cached
     */
    private function needsRebuild(mixed $cached): bool
    {
        return ! is_array($cached) || ($cached['fingerprint'] ?? null) !== $this->fingerprint()
            || ! is_array($cached['packages'] ?? null);
    }

    private function fingerprint(): string
    {
        $path = Path::join($this->vendorPath, 'composer', 'installed.json');
        $installed = is_file($path) ? file_get_contents($path) : false;

        return hash('sha256', serialize([$installed, $this->packagesToIgnore()]));
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
            throw new RuntimeException("Unable to read Composer package metadata at {$path}.", 0, $exception);
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
            throw new RuntimeException("Unable to read the application Composer file at {$path}.", 0, $exception);
        }

        $ignored = $composer['extra']['phenix']['dont-discover'] ?? [];

        return is_array($ignored) ? $ignored : [];
    }

    private function write(array $manifest): void
    {
        $directory = dirname($this->manifestPath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("The package cache directory {$directory} could not be created.");
        }

        if (! is_writable($directory)) {
            throw new RuntimeException("The package cache directory {$directory} must be writable.");
        }

        $temporaryPath = tempnam($directory, 'packages-');

        if ($temporaryPath === false) {
            throw new RuntimeException("Unable to create a temporary package manifest in {$directory}.");
        }

        $contents = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($manifest, true) . ';' . PHP_EOL;

        if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false || ! rename($temporaryPath, $this->manifestPath)) {
            @unlink($temporaryPath);

            throw new RuntimeException("Unable to write the package manifest at {$this->manifestPath}.");
        }
    }
}
