<?php

declare(strict_types=1);

namespace Phenix\Providers;

use Phenix\Views\Contracts\TemplateEngine as TemplateEngineContract;
use Phenix\Views\TemplateEngine;

class ViewServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [TemplateEngineContract::class];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        $this->bind(TemplateEngineContract::class, TemplateEngine::class)->setShared(true);
    }
}
