<?php

declare(strict_types=1);

namespace Phenix\Validation;

use Phenix\Providers\ServiceProvider;
use Phenix\Validation\Console\MakeRule;
use Phenix\Validation\Console\MakeType;

class ValidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            MakeRule::class,
            MakeType::class,
        ]);
    }
}
