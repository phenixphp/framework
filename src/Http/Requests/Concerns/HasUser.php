<?php

declare(strict_types=1);

namespace Phenix\Http\Requests\Concerns;

use Phenix\Auth\User;
use Phenix\Facades\Config;

trait HasUser
{
    public function user(): User|null
    {
        if ($this->request->hasAttribute(Config::get('auth.users.model', User::class))) {
            return $this->request->getAttribute(Config::get('auth.users.model', User::class));
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
}
