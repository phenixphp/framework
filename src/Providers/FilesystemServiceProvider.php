<?php

declare(strict_types=1);

namespace Phenix\Providers;

use Phenix\Contracts\Filesystem\File as FileContract;
use Phenix\Filesystem\File;
use Phenix\Filesystem\Storage;

class FilesystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bind(Storage::class);
        $this->bind(FileContract::class, File::class);
    }
}
