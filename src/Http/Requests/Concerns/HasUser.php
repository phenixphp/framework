<?php

declare(strict_types=1);

namespace Phenix\Http\Requests\Concerns;

use Phenix\Auth\User;
use Phenix\Facades\Config;

use function in_array;

trait HasUser
{
    public function user(): User|null
    {
        $key = Config::get('auth.users.model', User::class);

        if ($this->request->hasAttribute($key)) {
            return $this->request->getAttribute($key);
        }

        return null;
    }

    public function setUser(User $user): void
    {
        $this->request->setAttribute(Config::get('auth.users.model', User::class), $user);
    }

    public function hasUser(): bool
    {
        return $this->user() !== null;
    }

    public function can(string $ability): bool
    {
        $user = $this->user();

        if (! $user || ! $user->currentAccessToken()) {
            return false;
        }

        $abilities = $user->currentAccessToken()->getAbilities();

        if ($abilities === null) {
            return false;
        }

        return in_array($ability, $abilities, true) || in_array('*', $abilities, true);
    }

    public function canAny(array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if ($this->can($ability)) {
                return true;
            }
        }

        return false;
    }

    public function canAll(array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if (! $this->can($ability)) {
                return false;
            }
        }

        return true;
    }
}
