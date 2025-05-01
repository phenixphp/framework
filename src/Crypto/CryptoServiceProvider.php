<?php

declare(strict_types=1);

namespace Phenix\Crypto;

use Phenix\Facades\Config;
use Phenix\Providers\ServiceProvider;

class CryptoServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [Crypto::class];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        $this->bind(Crypto::class, fn (): Crypto => new Crypto(
            Config::get('app.key'),
            Config::get('app.previous_keys')
        ))->setShared(true);
    }
}
