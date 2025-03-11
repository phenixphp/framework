<?php

declare(strict_types=1);

namespace Phenix\Filesystem;

use Amp\File\File as FileHandler;
use Amp\File\Filesystem;
use Phenix\Contracts\Filesystem\File as FileContract;

use function Amp\File\filesystem;

class File implements FileContract
{
    private Filesystem $driver;

    public function __construct()
    {
        $this->driver = filesystem();
    }

    public function get(string $path): string
    {
        return $this->driver->read($path);
    }

    public function put(string $path, string $content): void
    {
        $this->driver->write($path, $content);
    }

    public function exists(string $path): bool
    {
        return $this->driver->exists($path);
    }

    public function isDirectory(string $path): bool
    {
        return $this->driver->isDirectory($path);
    }

    public function isFile(string $path): bool
    {
        return $this->driver->isFile($path);
    }

    public function createDirectory(string $path, int $mode = 0755): void
    {
        $this->driver->createDirectory($path, $mode);
    }

    public function openFile(string $path, string $mode = 'w'): FileHandler
    {
        return $this->driver->openFile($path, $mode);
    }

    public function getCreationTime(string $path): int
    {
        return $this->driver->getCreationTime($path);
    }

    public function getModificationTime(string $path): int
    {
        return $this->driver->getModificationTime($path);
    }

    public function listFiles(string $path): array
    {
        return $this->driver->listFiles($path);
    }

    public function deleteFile(string $path): void
    {
        $this->driver->deleteFile($path);
    }

    public function deleteDirectory(string $path): void
    {
        $this->driver->deleteDirectory($path);
    }
}
