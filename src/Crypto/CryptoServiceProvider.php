<?php

declare(strict_types=1);

namespace Phenix\Crypto;

use Phenix\Crypto\Contracts\Hasher;
use Phenix\Crypto\Exceptions\MissingKeyException;
use Phenix\Facades\Config;
use Phenix\Providers\ServiceProvider;

class CryptoServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [
            Crypto::class,
            Hasher::class,
        ];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        $this->bind(Crypto::class, function (): Crypto {
            $key = Config::get('app.key');

            if (empty($key)) {
                throw new MissingKeyException('The application key is not set.');
            }

            return new Crypto(
                $key,
                Config::get('app.previous_keys')
            );
        })->setShared(true);

        $this->bind(Hasher::class, Hash::class)->setShared(true);
    }
}
