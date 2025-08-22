<?php

declare(strict_types=1);

namespace Phenix\Mail;

use Phenix\Providers\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [MailManager::class];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        $this->bind(MailManager::class)->setShared(true);
    }
}
