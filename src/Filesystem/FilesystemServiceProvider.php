<?php

declare(strict_types=1);

namespace Phenix\Filesystem;

use Phenix\Filesystem\Contracts\File as FileContract;
use Phenix\Providers\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [Storage::class, FileContract::class];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        $this->bind(Storage::class);
        $this->bind(FileContract::class, File::class);
    }
}
