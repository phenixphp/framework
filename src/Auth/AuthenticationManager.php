<?php

declare(strict_types=1);

namespace Phenix\Auth;

use Phenix\Facades\Config;
use Phenix\Util\Date;

class AuthenticationManager
{
    private User|null $user = null;

    public function user(): User|null
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function validate(string $token): bool
    {
        $hashedToken = hash('sha256', $token);

        /** @var PersonalAccessToken|null $accessToken */
        $accessToken = PersonalAccessToken::query()
            ->whereEqual('token', $hashedToken)
            ->whereGreaterThan('expires_at', Date::now()->toDateTimeString())
            ->first();

        if (! $accessToken) {
            return false;
        }

        $accessToken->lastUsedAt = Date::now();
        $accessToken->save();

        /** @var class-string<User> $userModel */
        $userModel = Config::get('auth.users.model', User::class);

        /** @var User|null $user */
        $user = $userModel::find($accessToken->tokenableId);

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'withAccessToken')) {
            $user->withAccessToken($accessToken);
        }

        $this->setUser($user);

        return true;
    }
}
