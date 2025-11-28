<?php

declare(strict_types=1);

namespace Phenix\Auth;

use Phenix\Auth\Console\PersonalAccessTokensTableCommand;
use Phenix\Providers\ServiceProvider;

use function in_array;

class AuthServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            AuthenticationManager::class,
        ]);
    }

    public function register(): void
    {
        $this->bind(AuthenticationManager::class);
    }

    public function boot(): void
    {
        $this->commands([
            PersonalAccessTokensTableCommand::class,
        ]);
    }
}
