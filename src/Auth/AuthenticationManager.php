<?php

declare(strict_types=1);

namespace Phenix\Auth;

use Phenix\Facades\Cache;
use Phenix\Facades\Config;
use Phenix\Util\Date;

use function sprintf;

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

    public function increaseAttempts(string $clientIdentifier): void
    {
        $key = $this->getAttemptKey($clientIdentifier);

        Cache::set(
            $key,
            $this->getAttempts($clientIdentifier) + 1,
            Date::now()->addSeconds(
                (int) (Config::get('auth.tokens.rate_limit.window', 300))
            )
        );
    }

    public function getAttempts(string $clientIdentifier): int
    {
        $key = $this->getAttemptKey($clientIdentifier);

        return (int) Cache::get($key, fn (): int => 0);
    }

    public function resetAttempts(string $clientIdentifier): void
    {
        $key = $this->getAttemptKey($clientIdentifier);

        Cache::delete($key);
    }

    protected function getAttemptKey(string $clientIdentifier): string
    {
        return sprintf('auth:token_attempts:%s', $clientIdentifier);
    }
}
