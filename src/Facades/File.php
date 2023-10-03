<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Contracts\Filesystem\File as FileContract;
use Phenix\Runtime\Facade;

/**
 * @method static string get(string $path)
 * @method static bool put(string $path, string $content)
 * @method static bool exists(string $path)
 * @method static bool isDirectory(string $path)
 * @method static bool isFile(string $path)
 * @method static void createDirectory(string $path, int $mode = 0755)
 * @method static \Amp\File\File openFile(string $path, string $mode = 'w')
 *
 * @see \Phenix\Filesystem\File
 */
class File extends Facade
{
    public static function getKeyName(): string
    {
        return FileContract::class;
    }
}
