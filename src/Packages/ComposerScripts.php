<?php

declare(strict_types=1);

namespace Phenix\Packages;

use Phenix\Util\Path;

class ComposerScripts
{
    public static function postAutoloadDump(): void
    {
        $path = Path::join(getcwd(), 'storage', 'framework', 'cache', 'packages.php');

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
