<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\App;
use Phenix\Packages\PackageRegistry;
use Phinx\Migration\Manager;

use function in_array;
use function strlen;

class MigrationManager extends Manager
{
    protected function getMigrationFiles(): array
    {
        $files = parent::getMigrationFiles();
        $packages = App::make(PackageRegistry::class);
        $published = $packages->pathsToPublish();
        $excluded = [];

        foreach ($files as $file) {
            foreach ($published as $source => $destination) {
                $filePath = realpath($file);
                $target = $this->publishedTarget($file, $source, $destination);

                if ($target === null || $filePath === false) {
                    continue;
                }

                if ($this->isPublishedMigration($target, $filePath, $files)) {
                    $excluded[] = $file;
                }
            }
        }

        return array_values(array_diff($files, $excluded));
    }

    private function isPublishedMigration(string $target, string $filePath, array $files): bool
    {
        return basename($target) === basename($filePath)
            && is_file($target) && realpath($target) !== $filePath
            && in_array(realpath($target), array_map('realpath', $files), true);
    }

    private function publishedTarget(string $file, string $source, string $destination): string|null
    {
        $sourcePath = realpath($source);
        $filePath = realpath($file);

        if ($sourcePath === false || $filePath === false) {
            return null;
        }

        if ($sourcePath === $filePath) {
            return $destination;
        }

        $target = null;

        if (is_dir($sourcePath) && str_starts_with($filePath, rtrim($sourcePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
            $target = rtrim($destination, DIRECTORY_SEPARATOR) . substr($filePath, strlen($sourcePath));
        }

        return $target;
    }
}
