<?php

declare(strict_types=1);

namespace Phenix\Translation;

use Phenix\Providers\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [Translator::class];

        return $this->isProvided($id);
    }

    public function boot(): void
    {
        $this->bind(Translator::class, Translator::build(...))->setShared(true);
    }
}
