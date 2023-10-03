<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Runtime\Facade;

/**
 * @method static string get(string $path, string $mode = 'r')
 */
class Storage extends Facade
{
    public static function getKeyName(): string
    {
        return \Phenix\Filesystem\Storage::class;
    }
}
