<?php

declare(strict_types=1);

namespace Phenix\Filesystem\Contracts;

use Amp\Cancellation;

interface File
{
    public function get(string $path): string;

    public function put(string $path, string $content): void;

    public function exists(string $path): bool;

    public function isDirectory(string $path): bool;

    public function isFile(string $path): bool;

    public function createDirectory(string $path, int $mode = 0777): void;

    public function move(string $from, string $to): void;

    public function realPath(string $path, Cancellation|null $cancellation = null): string|false;

    public function getStatus(string $path): array|null;
}
